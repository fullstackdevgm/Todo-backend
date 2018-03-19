// !Global vars
var recordsPerPage = 50;

// !window.load
window.addEventListener('load', setUpPromoCodePage, false);

// !functions
function setUpPromoCodePage()
{
	var doc = document;
	
	loadUnusedPromoCodes(0, recordsPerPage);
	loadUsedPromoCodes(0, recordsPerPage);
	
	doc.getElementById('new_promo_note').addEventListener('keyup', shouldEnableCreatePromoCode, false);
	doc.getElementById('new_promo_time').addEventListener('keyup', shouldEnableCreatePromoCode, false);
};

function loadUnusedPromoCodes(offset, limit)
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
                	
	              	var promoCodes = response.promo_code_infos;
	              	var totalCount = parseInt(response.num_of_promo_codes, 10); //count of total records in server. Used for pagination
	              	var batchCount = promoCodes.length; //count of batch sent form server
	              	var creators = response.owner_display_names;
	              	
	               	var html = '';
	               	var paginationHtml = '';
	               	
	               	//headers
               		html += '	<div class="setting header">';
	               	html += '		<div class="promo_date">Creation Date</div>';
	               	html += '		<div class="promo_time">Months</div>';
	               	html += '		<div class="promo_grab_link">Link</div>';
	               	html += '		<div class="promo_name">Created by</div>';
	               	html += '		<div class="promo_note">Description</div>';
	               	html += '		<div class="promo_delete"></div>';
	               	html += '	</div>';

	               	if (batchCount > 0)
	               	{
		               	for (var i = 0; i < batchCount; i++)
		               	{
		               		var code = promoCodes[i];
		               		
			               	html += '	<div class="setting promo_code">';
			               	html += '		<div class="promo_date">' + displayHumanReadableDate(code.timestamp, false, true) + '</div>';
			               	html += '		<div class="promo_time">' + code.subscription_duration + '</div>';
			               	html += '		<input class="promo_grab_link" type="text" value="' + code.promolink + '" onclick="select(this)" oncontextmenu="onclick()"/>';
			               	html += '		<div class="promo_name">' + creators[code.creator_userid] + '</div>';
			               	html += '		<div class="promo_note">' + code.note + '</div>';
			               	html += '		<div class="promo_delete"><img onclick="confirmPromoCodeDeletion(\'' + code.promocode + '\')" src="https://s3.amazonaws.com/static.plunkboard.com/images/task/delete_off_icon.png"></div>';
			               	html += '	</div>';
		               	}
		            }
		            else
		            	html += '<p>There are no unused promo codes</p>';
		            	
		            if(totalCount > recordsPerPage)
		            {
		            	var pages = parseInt(totalCount/recordsPerPage) + 1;
		            	var currentPage = parseInt(offset/recordsPerPage) + 1;
		            	//console.log('currentPage: ' + currentPage );
		            	
		            	html += '	<div class="pagination" style="text-align:right;margin-top:10px;">';
		            	
		            	for (var i = 1; i <= pages; i++)
		            	{
		            		var separator = (i == pages) ? '' : '';	
		            		var pageClass = (i == currentPage) ?  'page selected' : 'page';
		            		var pageLink = (i == currentPage) ? 'javascript:void(0);' : 'javascript:loadUnusedPromoCodes(\'' + recordsPerPage * (i - 1) + '\', \'' + recordsPerPage+ '\')';
		            			
			            	html += '<a class="' + pageClass + '" href="' + pageLink + '"> ' + i + ' ' + separator + '</span>';
			            	
		            	}
		            	
		            	html += '	</div>';
		            }
		            	
	               	doc.getElementById('unused_promo_codes').innerHTML = html;
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

	var params = 'method=listPromoCodes';
    
    if(offset)
    	params += '&offset=' + offset;
    if(limit)
    	params += '&limit=' + limit;
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function confirmPromoCodeDeletion(promoCode)
{
	var bodyHTML = '<p>Are you sure you want to delete this promo code?</p>';
	var headerHTML = 'Delete Promo Code';
	var footerHTML = '';
	
	footerHTML += '<div class="button" onclick="deletePromoCode(\'' + promoCode + '\')">Chuck it!</div>';
	footerHTML += '<div class="button" onclick="hideModalContainer()">Cancel</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
};

function deletePromoCode(promoCode)
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
            		var bodyHTML = '<p>That promo code was successfully deleted.</p>';
            		var headerHTML = 'Success!';
            		var footerHTML = '';//<div class="button " onclick="function(){hideModalContainer();loadUnusedPromoCodes();}">Ok</div>';
            		
            		
            		displayModalContainer(bodyHTML, headerHTML, footerHTML);
            		setTimeout(function(){
	            		hideModalContainer();
	            		loadUnusedPromoCodes();
            		}	, 2500);
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

	var params = 'method=deletePromoCode&promoCode=' + promoCode;
    
	ajaxRequest.open("POST", ".", true);
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function loadUsedPromoCodes(offset, limit)
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
                	
	              	var promoCodes = response.promo_code_infos;
	              	var totalCount = parseInt(response.num_of_promo_codes, 10); //count of total records in server. Used for pagination
	              	var batchCount = promoCodes.length; //count of batch sent form server
	              	var creators = response.owner_display_names;
	              	
	               	var html = '';
	               	var paginationHtml = '';
	               	
	               	//headers
               		html += '	<div class="setting header">';
					html += '		<div class="promo_date">Consumption Date</div>';
					html += '		<div class="promo_name">Consumer</div>';
	               	html += '		<div class="promo_time">Months</div>';
	               	html += '		<div class="promo_date">Creation Date</div>';
	               	html += '		<div class="promo_name">Created by</div>';
	               	html += '		<div class="promo_note">Description</div>';
	               	html += '	</div>';

	               	if (batchCount > 0)
	               	{
		               	for (var i = 0; i < batchCount; i++)
		               	{
		               		var code = promoCodes[i];
		               		
			               	html += '	<div class="setting promo_code">';
			               	html += '		<div class="promo_date">' + displayHumanReadableDate(code.timestamp, false, true) + '</div>';
			               	html += '		<div class="promo_name">' + code.displayname + '</div>';
			               	html += '		<div class="promo_time">' + code.subscription_duration + '</div>';
			               	html += '		<div class="promo_date">' + displayHumanReadableDate(code.creation_timestamp, false, true) + '</div>';
			               	html += '		<div class="promo_name">' + creators[code.creator_userid] + '</div>';
			               	html += '		<div class="promo_note">' + code.note + '</div>';
			               	html += '	</div>';
		               	}
		            }
		            else
		            	html += '<p>There are no used promo codes</p>';
		            	
		            if(totalCount > recordsPerPage)
		            {
		            	var pages = parseInt(totalCount/recordsPerPage) + 1;
		            	var currentPage = parseInt(offset/recordsPerPage) + 1;
		            	//console.log('currentPage: ' + currentPage );
		            	
		            	html += '	<div class="pagination" style="text-align:right;margin-top:10px;">';
		            	
		            	for (var i = 1; i <= pages; i++)
		            	{
		            		var separator = (i == pages) ? '' : '';	
		            		var pageClass = (i == currentPage) ?  'page selected' : 'page';
		            		var pageLink = (i == currentPage) ? 'javascript:void(0);' : 'javascript:loadUnusedPromoCodes(\'' + recordsPerPage * (i - 1) + '\', \'' + recordsPerPage+ '\')';
		            			
			            	html += '<a class="' + pageClass + '" href="' + pageLink + '"> ' + i + ' ' + separator + '</span>';
			            	
		            	}
		            	
		            	html += '	</div>';
		            }
		            	
	               	doc.getElementById('used_promo_codes').innerHTML = html;
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

	var params = 'method=listUsedPromoCodes';
    
    if(offset)
    	params += '&offset=' + offset;
    if(limit)
    	params += '&limit=' + limit;
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function shouldEnableCreatePromoCode()
{
	var validated = false;
	var doc = document;
	var button = doc.getElementById('generate_promo_code_button');
	var promoNoteCharCount = doc.getElementById('new_promo_note').value.length;
	
	if (promoNoteCharCount >= 47)
		validated = true;

	if (validated)
	{
		button.setAttribute('class', 'button');
		button.addEventListener('click', generatePromoCode, false);
	}
	else
	{
		button.setAttribute('class', 'button disabled');
		button.removeEventListener('click', generatePromoCode, false);
	}
};

function generatePromoCode()
{
	var doc = document;
	var months = doc.getElementById('new_promo_time').value;
	var note = doc.getElementById('new_promo_note').value;
	
	
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

	                window.history.go(0);
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

	var params = 'method=createPromoCode&numberOfMonths=' + months + '&note=' + note;
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function selectLink(el)
{
	el.select();		
};
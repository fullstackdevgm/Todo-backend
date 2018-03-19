function setUpReferralsPage()
{
	var doc = document;
	
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
					displayReferralInfo(response);
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
                alert("unknown error " + e);
            }
		}
	}

	var params = 'method=getReferralStats';
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displayReferralInfo(response)
{
	var html = '';

	html += '	<div class="setting system">';
	html += '		<div class="user_count">Total Accounts From Referrals: ' + response.totalReferralAccounts + '</div>';
	html += '	</div>';

	html += '	<div class="setting system">';
	html += '		<div class="user_count">Total Month Increases Awarded to Referrers: ' + response.totalExtensions + '</div>';
	html += '	</div>';
    
    html += '	<div class="setting system">';
	html += '		<div class="user_count">Total Unique Referral Codes Used: ' + response.totalUniqueCodes + '</div>';
	html += '	</div>';
    
    html += '	<div class="setting system">';
	html += '		<div class="user_count">Total Unique Referral Codes Resulting in Purchases: ' + response.totalUniqueCodesPurchased + '</div>';
	html += '	</div>';
    
    var topReferrerString = '';
    var topReferrers = response.topReferrers;
    for(var i = 0; i < topReferrers.length; i++)
    {
        var referrer = topReferrers[i];
        var username = referrer.username;
        var count = referrer.referralcount;
        var userid = referrer.userid;
        var extensioncount = referrer.extensioncount;
        
        if(topReferrerString.length > 0)
            topReferrerString += ', ';
        
        topReferrerString += '<a href="?section=users&userid=' + userid + '">' +  username + '</a> (' + count + ' referrals, ' + extensioncount + ' extensions)';
        
    }
    
    html += '	<div class="setting system">';
	html += '		<div class="user_count">Top Referrers By Link Usage: ' + topReferrerString + '</div>';
	html += '	</div>';
    
    
    topReferrerString = '';
    topReferrers = response.topPaidReferrers;
    for(var i = 0; i < topReferrers.length; i++)
    {
        var referrer = topReferrers[i];
        var username = referrer.username;
        var count = referrer.extensioncount;
        var referralcount = referrer.referralcount;
        var userid = referrer.userid;
        
        if(topReferrerString.length > 0)
            topReferrerString += ', ';
        
        topReferrerString += '<a href="?section=users&userid=' + userid + '">' +  username + '</a> (' + count + ' extensions, ' + referralcount + ' referrals)';
        
    }
    
    html += '	<div class="setting system">';
	html += '		<div class="user_count">Top Referrers By Account Extension: ' + topReferrerString + '</div>';
	html += '	</div>';

	// New accounts created from a referral link
	html += '<h2>New Accounts From Referrals</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxAccountsCreated, response.dailyAccountsCreated);
    
	
	// month increases given to referrers when people pay
	html += '<h2>Month Increases Awarded to Referrers</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxAccountsExtended, response.dailyAccountsExtended);
    

	// Unique referrals used
	html += '<h2>Unique Referral Codes Used</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxUniqueCodesUsed, response.dailyUniqueCodesUsed);
    
	
	var doc = document;
	doc.getElementById('referral_stats_container').innerHTML = html;
};


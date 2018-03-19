// !Global vars
var recordsPerPage = 50;

// !window.load
// this was added to the PHP so other pages could use these methods
//window.addEventListener('load', setUpSystemInfoPage, false);

// !functions
function setUpSystemStatsPage()
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
					displaySystemStats(response);
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
    
	var params = 'method=getSystemTotals';
    
	ajaxRequest.open("POST", ".", true);
    
	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};


function displaySystemStats(response)
{
	var html = '';
    
    html += '  <style>';
    html += '       .stat-wrap{display: inline-block;font-size: 6rem;margin: 10px;text-align: center;vertical-align: center; min-width: 15rem;}';
    html += '       .stat_number{font-weight:bold;background: none repeat scroll 0 0 rgba(0, 0, 0, 0.7);border: 3px solid black;color: white;font-size: 5rem;font-weight: bold;line-height: 4rem;margin-bottom: 10px;padding: 10px;}';
    html += '       .stat_title{font-size: 1.5rem;}';
    html += '   </style>';
    
	html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number" style="background-color:Mediumblue">' + response.totalUserCount + '</div>';
	html += '		<div class="stat_title">Total Users</div>';
	html += '	</div>';

    
    html += '	<div class="stat-wrap">';
//	html += '		<div class="stat_number" style="background-color:blue">10000</div>';
	html += '		<div class="stat_number" style="background-color:Mediumblue">' + response.newUsersToday + '</div>';
	html += '		<div class="stat_title">Users Today</div>';
	html += '	</div>';

    html += '</br>';
    
    
    html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number" style="background-color:green">' + response.stripeMonthPurchasesToday + '</div>';
	html += '		<div class="stat_title">S. Month Today</div>';
	html += '	</div>';
    
    html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number" style="background-color:green">' + response.stripeYearPurchasesToday + '</div>';
	html += '		<div class="stat_title">S. Year Today</div>';
	html += '	</div>';
    
    html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number" style="background-color:green">' + response.iapMonthPurchasesToday + '</div>';
	html += '		<div class="stat_title">IAP Month Today</div>';
	html += '	</div>';
    
    html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number" style="background-color:green">' + response.iapYearPurchasesToday + '</div>';
	html += '		<div class="stat_title">IAP Year Today</div>';
	html += '	</div>';
    
    html += '</br>';
    
    
    html += '	<div class="stat-wrap">';
//	html += '		<div class="stat_number">250000</div>';
	html += '		<div class="stat_number">' + response.totalTaskCount + '</div>';
	html += '		<div class="stat_title">Tasks</div>';
	html += '	</div>';

    html += '	<div class="stat-wrap">';
//	html += '		<div class="stat_number">22343</div>';
	html += '		<div class="stat_number">' + response.totalCompletedTaskCount + '</div>';
	html += '		<div class="stat_title">Completed Tasks</div>';
	html += '	</div>';

    html += '	<div class="stat-wrap">';
	html += '		<div class="stat_number">' + response.totalListCount + '</div>';
	html += '		<div class="stat_title">Lists</div>';
	html += '	</div>';

    
	var doc = document;
	doc.getElementById('system_stats_results').innerHTML = html;
    
    setInterval ("history.go(0);", 60000); // every minute   
};  



function setUpSystemInfoPage()
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
					displaySystemInfo(response);
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

	var params = 'method=getSystemStats';
    
	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function displaySystemInfo(response)
{
	var html = '';
	html += '	<div class="setting system">';
	html += '		<div class="user_count">Total Users: ' + response.totalUserCount + '</div>';
	html += '	</div>';
	
	// New Users
	html += '<h2>New Users</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxNewUsers, response.dailyNewUsers);
    
	// Stripe Month Purchases
	html += '<h2>Stripe Month Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxMonthStripePurchases, response.dailyMonthStripePurchases);
	
	// Stripe Year Purchases
	html += '<h2>Stripe Year Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxYearStripePurchases, response.dailyYearStripePurchases);
	
	// Renewing IAP Month Purchases
	html += '<h2>Renewing Month In-App Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxRenewingMonthIAPPurchases, response.dailyRenewingMonthIAPPurchases);
	
	// Renewing IAP Year Purchases
	html += '<h2>Renewing Year In-App Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxRenewingYearIAPPurchases, response.dailyRenewingYearIAPPurchases);
	
	// Renewing Google Play Month Purchases
	html += '<h2>Renewing Month Google Play Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxRenewingMonthGooglePlayPurchases, response.dailyRenewingMonthGooglePlayPurchases);
	
	// Renewing Google Play Year Purchases
	html += '<h2>Renewing Year Google Play Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxRenewingYearGooglePlayPurchases, response.dailyRenewingYearGooglePlayPurchases);
	
	
	// IAP Month Purchases
	html += '<h2>Month In-App Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxMonthIAPPurchases, response.dailyMonthIAPPurchases);
	
	// IAP Year Purchases
	html += '<h2>Year In-App Purchases</h2>';
    html += statBarWrapperForDailyStat(response.dailyMaxYearIAPPurchases, response.dailyYearIAPPurchases);
	
	var doc = document;
	doc.getElementById('system_info_results').innerHTML = html;
};

function getQueryVariable(variable)
{
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    //console.log('Query variable %s not found', variable);
}
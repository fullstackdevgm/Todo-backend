var defaultUserImgUrl = 'https://fbcdn-profile-a.akamaihd.net/static-ak/rsrc.php/v1/yo/r/UlIqmHJn-SK.gif';
shouldShowSupportedBrowsersMessage();

if (!window.location.origin || typeof (window.location.origin) === 'undefined') {
    window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
}

function calculateMainContainersSize()
{
	var doc = document;
	
	var headerWrapEl = doc.getElementById('header');
	var infoHeaderWrapEl = doc.getElementById('info-header');
	var controlsWrapEl = doc.getElementById('controls');
	var contentWrapEl = doc.getElementById('content');
	var controlContainerEl = doc.getElementById('control_container');
	var toolbarEl = doc.getElementById('filter_banner');
	var filterEl = doc.getElementById('task_toolbar');

	var viewportHeight = window.innerHeight;
    var viewportWidth = window.innerWidth;
    if (infoHeaderWrapEl && infoHeaderWrapEl.clientHeight) {
        viewportHeight -= infoHeaderWrapEl.clientHeight;
    }
	var contentHeight = (viewportHeight - headerWrapEl.clientHeight) + 'px';
	
	try
	{
		if (getUserOS() == 'Windows' && getUserBrowser() == 'Safari')
			contentWrapEl.style.marginLeft = '-5px';
			
		controlsWrapEl.style.height = contentHeight;
		contentWrapEl.style.height = contentHeight;
		contentWrapEl.style.width = (viewportWidth - controlsWrapEl.clientWidth - 3) + 'px';
		
	
		controlContainerEl.style.height = (controlsWrapEl.clientHeight - 201) + 'px';
		doc.getElementById('task_sections_wrapper').style.height = (contentWrapEl.clientHeight- 113) + 'px';
		
		toolbarEl.style.width = (contentWrapEl.clientWidth ) - 30  + 'px';
		filterEl.style.width = (contentWrapEl.clientWidth ) + 'px';
		
	}
	catch(e){}
};

function shouldShowSupportedBrowsersMessage()
{
	if (/MSIE (\d+\.\d+);/.test(navigator.userAgent))
	{ //test for MSIE x.x;
		var ieversion=parseInt(RegExp.$1, 10); // capture x.x portion and store as a number
		if (ieversion < 9)
			window.location = 'http://' + location.hostname + location.pathname + 'html/unsupportedBrowser.html';
	}
	//window.location = 'http://' + location.hostname + location.pathname + 'html/unsupportedBrowser.html';
};

function TDOEncodeEntities(s)
{
	return $("<div/>").text(s).html();
};

function TDODeencodeEntities(s)
{
	return $("<div/>").html(s).text();
};

function stripHtmlTags(htmlString)
{
	var result = htmlString.replace(/(<([^>]+)>)/ig,"");
	
	return result.replace(/&amp;/ig, '&');
};
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


//displayGlobalErrorMessage function
//purpose: displays message in the 'messageContainer'and displays it
//parameter: a message (string)
function displayGlobalErrorMessage(message)
{
//    alert(message);

	if( message == 'SyntaxError: Unexpected end of input') return;

	var messageEl = document.getElementById('messageContainer');
	var dismissMessageEl = document.createElement('span');
	dismissMessageEl.innerHTML = '';
	dismissMessageEl.setAttribute('onclick', 'hideGlobalErrorMessage()');
	dismissMessageEl.setAttribute('id', '');

	//var dismissMessageElHTML = '<span id="dismissGlobalMessageBtn"></span>';

	//set up message container
	messageEl.innerHTML = message;
	messageEl.appendChild(dismissMessageEl);
    messageEl.className = '';
    messageEl.style = '';

	//display message
	messageEl.style.display = 'block';
	
	console.log('error: ' + message);
};

//hideGlobalErrorMessage function
//purpose: clears the 'messageContainer' div and hides it
//parameter: none
function hideGlobalErrorMessage()
{
	var messageEl = document.getElementById('messageContainer');
	messageEl.style.display = 'none';
	messageEl.innerHTML = '';
	messageEl.style.background = '#CB2521';
	//messageEl.style.color = 'white';
};

function displayGlobalMessage(message, success)
{
	var messageEl = document.getElementById('messageContainer');


	displayGlobalErrorMessage(message);
    if (success === true) {
        messageEl.className = 'success';
        messageEl.style = '';
    } else {
        messageEl.style.background = 'rgb(230, 108, 44)';
    }
	//messageEl.style.color = 'black';
};


// This function looks to see if a system notification message is present and
// whether we've ever shown it. If we haven't ever shown it, it will force it
// to be shown and write a cookie to prevent it from being shown (in alert form
// again).
function showInitialSystemNotificationMessageIfExists()
{
	var notificationIDInput = document.getElementById('system_notification_id');
	if (notificationIDInput == null)
		return;
	
	var notificationID = notificationIDInput.value;
	if (notificationID == null)
		return; // no message present
	
	// Check to see if we've already shown this message and ignore it if we have
	var shownNotificationID = getCookieForName('SystemNotificationID');
	if ( (shownNotificationID != null) && (shownNotificationID == notificationID) )
		return; // we've already shown this message
	
	// Set the cookie so we don't show this message again
	SetCookie('SystemNotificationID', notificationID);
	
	// Show the system notification message
	showSystemNotificationMessage();
};

function showSystemNotificationMessage()
{
	var doc = document;
	
	var notificationMessage = doc.getElementById('system_notification_message').value;
	var learnMoreURL = doc.getElementById('system_notification_learn_more_link').value;
	
	var bodyHTML = '';
	var headerHTML = labels.service_alert ;
	var footerHTML = '';
	
	bodyHTML += '<div style="margin:10px auto;width: 400px;font-size:1.2em;line-height:1.4em"><p>';
	bodyHTML += '<p>' + notificationMessage + '</p>';
    bodyHTML += '<p style="text-align:center;"><a href="' + learnMoreURL + '" target="_blank" title="' + labels.read_more_information_about_this_scheduled_maintenance + '" style="text-decoration:underline;font-weight:bold;">' + labels.learn_more + '</a></p>';
	bodyHTML += '<hr/>';
    bodyHTML += '<p style="font-size:smaller;text-align:center;">' + labels.to_view_the_current_status_of + '</p>';
	bodyHTML += '</p></div>';
	
//	footerHTML += '<div class="button" onclick="window.open(\'' + learnMoreURL + '\')">Learn More</div>';
	footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.close + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	doc.getElementById('modal_overlay').onclick = null;
};

//addLeadingZero function
//purpose: returns a 2-digit int with a leading zero (if necessary)
//parameters: a number, i, which can be an int or a String
function addLeadingZero(i)
{
	i = parseInt(i, 10);
	if(i < 10)
		return "0" + i;
	else
		return i;
};

//Returns the unix timestamp of the given date at 23:59:59 in the
//current timezone
function getEndOfDayOfDate(unixTimestamp)
{
    var date = new Date(unixTimestamp * 1000);
    date.setHours(23);
    date.setMinutes(59);
    date.setSeconds(59);
    
    return date.getTime() / 1000;
}

//Returns the unix timestamp of the given date at 0:00:00 in the
//current timezone
function getStartOfDayOfDate(unixTimestamp)
{
    var date = new Date(unixTimestamp * 1000);
    date.setHours(0);
    date.setMinutes(0);
    date.setSeconds(0);
    
    return date.getTime() / 1000;
}

//DisplayHumanReadableDate function
//purpose: takes a unix timestamp and returns a human readable formatted date
//parameter: unix timestamp and a timeformat
function displayHumanReadableDate(unixTimestamp, showDueTime, showFullYear, hideCurrentYear)
{
	var humanReadableDate = '';

	if (unixTimestamp == 0)
		humanReadableDate = taskStrings.noDate;
	else
	{
        //Commenting out these timezone changes because they could cause problems in cases where users
        //have never set up their timezone settings and we have inferred their timezone incorrectly.
//		var timezoneOffset = parseInt(document.getElementById('timezone').value, 10);
//		var dateFromUnix = new Date((unixTimestamp + timezoneOffset) * 1000);

		var currDate = new Date();
        var dateFromUnix = new Date(unixTimestamp * 1000);
		
		var	year = dateFromUnix.getFullYear();
		var	month = dateFromUnix.getMonth();
		var	day = dateFromUnix.getDate();
		var	hour = dateFromUnix.getHours();
		var	minutes = dateFromUnix.getMinutes();
		var meridian = '';

		//set up date string
		var dayString = daysOfWeekStrings[(new Date(year, month, day)).getDay()].substr(0,3);  //daysOfWeekStrings is in lang js file
		var monthString = monthsStrings[month].substr(0,3);
		humanReadableDate += dayString + ' ' + monthString + ' ' + day;

		//set up year string
		if (showFullYear) {
			if (hideCurrentYear) {
				if (year !== currDate.getFullYear()) {
					humanReadableDate += ', ' + year;
				}
			} else {
				humanReadableDate += ', ' + year;
			}
		}

		//prep am/pm hour format
		if(hour > 12)
		{
			hour -=12;
			meridian = 'pm';
		}
		else if (hour == 12)
			meridian = 'pm';
		else
			meridian = 'am';

		if(hour == 0 && meridian == 'am')
			hour = 12;

		//set up time string
		if(showDueTime)
		{
			if ((!isNaN(hour) && !isNaN(minutes)) || (hour.length > 0 && minutes.length > 0))
				humanReadableDate += '@'+ addLeadingZero(hour) +':'+ addLeadingZero(minutes) + meridian;
		}
	}
	return humanReadableDate;
};

function displayHumanReadableTime(unixDate)
{
	var humanReadableTime = '';
    
    //Commenting out these timezone changes because they could cause problems in cases where users
    //have never set up their timezone settings and we have inferred their timezone incorrectly.
//	var timezoneOffset = parseInt(document.getElementById('timezone').value, 10);
//	var sDate = new Date((unixDate + timezoneOffset) * 1000);
    var sDate = new Date(unixDate * 1000);

	var	hour = sDate.getHours();
	var	minutes = sDate.getMinutes();
	var meridian = 'am';

	//prep am/pm hour format
	if(hour == 0)
	{
		hour = 12;
		meridian = 'am';
	}
	else if(hour > 12)
	{
	    hour -=12;
	    meridian = 'pm';   	
	}
	else if (hour == 12)
		meridian = 'pm';

	

	//set up time string
    //if ((!isNaN(hour) && !isNaN(minutes)) || (hour.length > 0 && minutes.length > 0))
	    humanReadableTime += addLeadingZero(hour) +':'+ addLeadingZero(minutes) + meridian;

	    return humanReadableTime;
};

function monthDayFromTimestamp(unixTimestamp)
{
	if (unixTimestamp == 0)
		return 0;
	
	var dateValue = new Date(unixTimestamp * 1000);
	var month = dateValue.getMonth() + 1;
	var day = dateValue.getDate();
	
	var dateString = month + "/" + day;
	return dateString;
};


function selectLink(el)
{
	el.select();
};

function statBarWrapperForDailyStat(maxCount, countArray)
{
    var html = '<div class="stat_bar_wrapper">';
	
	for (var i = 0; i < countArray.length; i++)
	{
		html += '<div class="stat_bar">';
		var dailyInfo = countArray[i];
		var percentage = 100 * (dailyInfo.count / maxCount);
		
		html += '<div class="stat_value" style="height:' + percentage + '%;">';
		html += dailyInfo.count;
		html += '</div>';
		
		html += '<div class="stat_date">' + monthDayFromTimestamp(dailyInfo.timestamp) + '</div>';
		
		html += '</div>'
	}
	
	html += '</div>';
    return html;
};


//scrollUpViewPort function
//purpose: scrolls up the viewport upward if the user clicks within the tolerance set in the function
//parameters: a window event
function scrollUpViewport(event)
{
 	var viewportHeight = window.innerHeight;
  	var heightOffset = window.pageYOffset;
  	var yCoord = event.clientY;
  	var xCoord = event.clientX;
  	var tolerance = 40;

  	if (yCoord + tolerance > viewportHeight)
  	{
  		var scrollTarget = yCoord + heightOffset - viewportHeight + tolerance;
  		var currentScrollPosition = yCoord + heightOffset - viewportHeight;
  		while (scrollTarget > currentScrollPosition)
  		{
  			window.scrollTo(0, currentScrollPosition);
  			currentScrollPosition += 2;
  		}
  	}
}

function scrollToElement(element) {
    //Find the y value of the element
    var el_id = element.getAttribute('id')
    var section_wrapper = jQuery('#task_sections_wrapper');
    section_wrapper.scrollTop(jQuery('#' + el_id).position().top);
}

/*
function linkify (text)
{
	var linkfiedText = new String();
	var urlPattern = new RegExp('^[a-zA-Z0-9\-\.]+\.(com|org|net|mil|edu|COM|ORG|NET|MIL|EDU)$', 'gi');
	//var urlPattern = /((ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

	alert(urlPattern.test(text));
	//var replace = "<a href=\"$1//$2$3$4\">$1//$2$3$4</a>";
	var replace = "ketchup";
	linkifiedText = '\n' + text + '\n' + text.replace(urlPattern , replace);

	return linkifiedText;
}
*/


function uuid()
{
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
															  var r = (d + Math.random()*16)%16 | 0;
															  d = d/16 | 0;
															  return (c=='x' ? r : (r&0x7|0x8)).toString(16);
															  });
    return uuid;
};


//displayModalWindow function
//purpose: brings the modal window element to the foreground and executes the popCall javascript function
//parameters: the modalwindow id, an optional javascript function
function displayModalWindow (id, popCall)
{
	if(id == null)
		alert("The id for your modal window was not set");
	else
	{
		//fill form
		document.getElementById("MODAL_TITLE").innerHTML = document.getElementById(id + '_title').innerHTML;
		document.getElementById("MODAL_CONTENT").innerHTML = document.getElementById(id + '_body').innerHTML;
		document.getElementById("MODAL_FOOTER").innerHTML = document.getElementById(id + "_footer").innerHTML;
	}

	//center modal window
	var modalHeight = document.getElementById("MODAL_WINDOW").clientHeight;
	var modalWidth = document.getElementById("MODAL_WINDOW").clientWidth;
	document.getElementById("MODAL_WINDOW").style.marginTop = "-" + modalHeight/2  + "px";
	document.getElementById("MODAL_WINDOW").style.marginLeft = "-" + modalWidth/2 +"px";

	//make modal window visible
	document.getElementById("OVERLAY").style.background ="rgba(255,255,255,0.1)"
	document.getElementById("OVERLAY").style.zIndex = "600";
	//document.getElementById("MODAL_WINDOW").style.zIndex = "200";

	//trigger popCal javascript
	if (popCall != null)
		popCall();
};

//hideModalWindow function
//purpose: hides the modal window element
//parameters: none
function hideModalWindow ()
{
	//hide modal window
	document.getElementById("OVERLAY").style.background ="rgba(0,0,0,0)";
	document.getElementById('OVERLAY').style.zIndex = '-1';
	document.getElementById('MODAL_WINDOW').style.zIndex = '-1';

	document.getElementById("MODAL_CONTENT").style.height = "auto";
	document.getElementById("MODAL_CONTENT").style.width = "auto";

	//offset modal window
	document.getElementById("MODAL_WINDOW").style.marginTop = "-999px";
	document.getElementById("MODAL_WINDOW").style.marginLeft = "-999px";

	//temp call
	hideModalOverlay();
};


//getUserBrowser function
//purpose: returns the common name of the user's browser
function getUserBrowser()
{
	var agent = navigator.userAgent.toLowerCase ();

    var browser = "Unknown browser";
    if (agent.search ("msie") > -1) {
        browser = "Internet Explorer";
    }
    else {
        if (agent.search ("firefox") > -1) {
            browser = "Firefox";
        }
        else {
            if (agent.search ("opera") > -1) {
                browser = "Opera";
            }
            else {
                if (agent.search ("safari") > -1) {
                    if (agent.search ("chrome") > -1) {
                        browser = "Google Chrome";
                    }
                    else {
                        browser = "Safari";
                    }
                }
            }
        }
    }

    return browser;
};


function getUserOS()
{
	var OSName="Unknown OS";
	
	if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
	if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
	if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
	if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";
	
	return OSName;

};

function displayClickToDismissOverlay(jsFunction, taskId)
{
	var doc = document;
	var overlay = doc.getElementById('click_to_dismiss_overlay');

	overlay.style.display = 'block';
	overlay.style.height = '100%';
	overlay.style.width = '100%';

	overlay.onclick = function(){jsFunction(taskId);};
};

function hideClickToDismissOverlay()
{
	var doc = document;
	var overlay = doc.getElementById('click_to_dismiss_overlay');

	overlay.style.height = '0';
	overlay.style.width = '0';
	overlay.display = 'none';
	overlay.onclick = null;
};

function displayModalOverlay(customBgColor, customInnerHTML)
{
	var doc = document;
	var overlay = doc.getElementById('modal_overlay');

	if(customBgColor)
		overlay.style.backgroundColor = customBgColor;
		
	if(customInnerHTML)
		doc.getElementById('modal_overlay_message').innerHTML = customInnerHTML;
			
	overlay.style.display = 'block';

	overlay.onclick = function(){
		hideModalOverlay();
		hideModalContainer();
        hideGlobalErrorMessage();
	};
};

function hideModalOverlay()
{
	var doc = document;
	var overlay = doc.getElementById('modal_overlay');

    doc.getElementById('modal_overlay_message').innerHTML = '';
	overlay.setAttribute('style', '');/*

	overlay.style.height = '0';
	overlay.style.width = '0';
	overlay.style.display = 'none';
*/
    overlay.classList.remove('special');
	//overlay.addEventListener('click', hideModalContainer, false);
};

function displayModalContainer(bodyHTML, headerHTML, footerHTML, customBgColor)
{
	resetModalContainer();
	
	var doc = document;
	var modalContainer = doc.getElementById('modal_container');
	var headerContainer = doc.getElementById('modal_header');
	var footerContainer = doc.getElementById('modal_footer');
	modalContainer.style.display = 'block';

	if(bodyHTML != '')
		doc.getElementById('modal_body').innerHTML = bodyHTML;

	if(headerHTML)
	{
		headerContainer.style.display = 'block';
		headerContainer.innerHTML = headerHTML;
	}
	if(footerHTML)
	{
		footerContainer.style.display = 'block';
		footerContainer.innerHTML = footerHTML;
	}
    setTimeout(function () {
        // centerElementInViewPort(modalContainer);
        setTimeout(function () {
            modalContainer.style.opacity = 1;
        }, 0);
    }, 0);

	displayModalOverlay(customBgColor);
};

function hideModalContainer()
{	
	var doc = document;
	var modalContainer = doc.getElementById('modal_container');
	
	
	
	modalContainer.style.display = 'none';
	hideModalOverlay();
	resetModalContainer();
};

function disableOnClickDismissalOfModalContainer()
{
    var overlay = document.getElementById('modal_overlay');
    overlay.setAttribute('onclick', '');
}

function resetModalContainer()
{
	var doc = document;
	
	var modalContainer = doc.getElementById('modal_container');
	var body = doc.getElementById('modal_body');
	var header = doc.getElementById('modal_header')
	var footer = doc.getElementById('modal_footer')
	var err = doc.getElementById('modal_err')
	
	body.innerHTML = '';
	header.innerHTML =  '';
	footer.innerHTML = '';
	err.innerHTML = '';
	
	
	modalContainer.setAttribute('style', '');
	body.setAttribute('style', '');
	header.setAttribute('style', '');
	footer.setAttribute('style', '');
	err.setAttribute('style', '');
};

function displayErrorInModalContainer(errMsg)
{
	var doc = document;
	var errContainer = doc.getElementById('modal_err');

	errContainer.innerHTML = errMsg;
};

function centerElementInViewPort(anElement, trueCenter)
{
	//anElement must have position:absolute in its css
	var viewportHeight = document.documentElement.clientHeight;
	var viewportWidth = document.documentElement.clientWidth;

	var xOrigin = anElement.clientWidth/2;
	var yOrigin = anElement.clientHeight/2;

    anElement.style.marginTop = '-' + (parseInt(yOrigin) - 23)  + 'px';
    anElement.style.marginLeft = '-' + xOrigin + 'px';
};

function centerElementInElement(anElement, parentElement)
{
    var parentHeight = parentElement.clientHeight;
    var parentWidth = parentElement.clientWidth;
    
    var xOrigin = parentWidth/2 - anElement.clientWidth/2;
	var yOrigin = parentHeight/3 - anElement.clientHeight/2;

	anElement.style.top = yOrigin + 'px';
	anElement.style.left = xOrigin + 'px';
}

function stopEventPropogation(e)
{
    if (!e)
      e = window.event;

    //IE9 & Other Browsers
    if (e.stopPropagation)
    {
      e.stopPropagation();
    }
    //IE8 and Lower
    else
    {
      e.cancelBubble = true;
    }
};

function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

function createFragment(htmlStr)
{
    var frag = document.createDocumentFragment(),
        temp = document.createElement('div');
    temp.innerHTML = htmlStr;
    while (temp.firstChild) {
        frag.appendChild(temp.firstChild);
    }
    return frag;
};

function trim(str)
{
    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
};

function SetCookie(cookieName,cookieValue)
{
    document.cookie = cookieName+"="+escape(cookieValue);
};

function SetCookieAndLoad(cookieName,cookieValue,urlToLoad)
{
    urlToLoad = urlToLoad || null;
    
   	SetCookie(cookieName, cookieValue);

    if(urlToLoad == null)
        window.location.href = '.';
    else
        window.location.href = urlToLoad;
};

function getCookieForName(cookieName)
{
    var ARRcookies=document.cookie.split(";");
    for (var i=0;i<ARRcookies.length;i++)
    {
        var x = ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
        var y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g,"");
        if (x == cookieName)
        {
            return unescape(y);
        }
    }
}

function displaySendFeedbackModal()
{
	var doc = document;
	var bodyHTML = '';
	var headerHTML = labels.send_feedback ;
	var footerHTML = '';
	
    bodyHTML += '<div class="feedback-modal-body">';
	bodyHTML += '	<div class="labeled_text_input_container">';
    bodyHTML += '		<label class="">' + labels.subject + '</label>';
	bodyHTML += '		<input id="feedback_subject" class="label_input feedback_subject" type="text" name="subject" value="'+labels.todo_cloud_feedback +'">';
	bodyHTML += '	</div>';
	bodyHTML += '	<div class="labeled_text_input_container">';
	bodyHTML += '		<label class="">'+labels.message+'</label>';
	bodyHTML += '		<textarea id="feedback_message" class="label_input feedback_message"  placeholder="' + labels.todo_cloud_feedback + '"></textarea>';
	bodyHTML += '	</div>';
	bodyHTML += '   <label>'+labels.reporting_an_issue +'</label>';
	bodyHTML += '	<p>'+labels.please_include_the_following +'</p>';
	bodyHTML += '	<ul class="simple_list">';
	bodyHTML += '		<li>'+labels.operating_system_and_version +'</li>';
	bodyHTML += '		<li>'+labels.browser_and_version +'</li>';
    bodyHTML += '		<li>' + labels.step_by_step_description_of_how + '</li>';
	bodyHTML += '	</ul>';
    bodyHTML += '</div>';
	
	footerHTML += '<div class="button" onclick="hideModalContainer()">' + labels.cancel + '</div>';
	footerHTML += '<div id="create_list_ok_button" class="button" foo="bar" onclick="submitFeedback()">' + labels.send + '</div>';
	
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	
	//doc.getElementById('modal_container').style.top = '110px';

	var feedbackEl = doc.getElementById('feedback_message');
		feedbackEl.focus();
		feedbackEl.value = labels.dear_todo_cloud_team + ', \n\n';
	
	
};



function submitFeedback()
{
    
    var post_data = {};
    post_data["fromName"] = jQuery('#userName').val();
    post_data["subject"] = jQuery('#feedback_subject').val();
    post_data["message"] = jQuery('#feedback_message').val();
    post_data["method"] = "sendFeedback";
	
	hideModalContainer();
	
	console.log("posting data");
	jQuery.post(".", post_data, function(data){
	    
	    console.log(data);
	    
	    if (data.success)
	    {
            var headerHTML = labels.thank_you_e;
            var bodyHTML = labels.your_feedback_was_sent_successfully;
            var footerHTML = '<div class="button" onclick="hideModalContainer()">'+labels.ok +'</div>';
        
            displayModalContainer(bodyHTML,	headerHTML, footerHTML);
	    }
	    else
	    {
            if(data.error == "authentication")
            {
                //make the user log in again
                history.go(0);
            }
            else
            {
                //Display error message
                displayGlobalErrorMessage(labels.there_was_an_error_in_sending_this_message);
            }
	    }
	    
	}, "json");
	
}

function submitFeedback_()
{
	var ajaxRequest = getAjaxRequest();
	if(!ajaxRequest)
		return false;
	
	var doc = document;
	var fromName = doc.getElementById('userName').value;
	var subject  = doc.getElementById('feedback_subject').value;
	var message = doc.getElementById('feedback_message').value;

	hideModalContainer();
	
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
                    //Confirm successful submition
                    var headerHTML = labels.thank_you_e;
                    var bodyHTML = labels.your_feedback_was_sent_successfully;
                    var footerHTML = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
                    
                    
                    displayModalContainer(bodyHTML,	headerHTML, footerHTML);
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
                        //Display error message
                        displayGlobalErrorMessage(labels.there_was_an_error_in_sending_this_message);
                    }
                }
            }
            catch(e)
            {
                alert("Excetion :" + e);
            }
		}
	}

	var params = "method=sendFeedback&fromName="+fromName+"&subject="+subject+"&message="+message;

	ajaxRequest.open("POST", ".", true);

	//Send the proper header information along with the request
	ajaxRequest.setRequestHeader("content-type", "application/x-www-form-urlencoded");
	ajaxRequest.send(params);
};

function bindEvent(eventName, eventHandler, useEventCapture)
{
    if (this.addEventListener)
    {
        this.addEventListener(eventName, eventHandler, useEventCapture || false); 
    }
    else if (this.attachEvent)
    {
        this.attachEvent('on' + eventName, eventHandler);
    }
    
}

function unbindEvent(eventName, eventHandler, useEventCapture)
{
    if(this.removeEventListener)
    {
        this.removeEventListener(eventName, eventHandler, useEventCapture);
    }
    else if(this.detachEvent)
    {
        this.detachEvent('on' + eventName, eventHandler);
    }
}
		
HTMLElement.prototype.bindEvent = bindEvent;
HTMLElement.prototype.unbindEvent = unbindEvent;

function isDescendant(parent, child) 
{
     var node = child.parentNode;
     while (node != null) 
     {
         if (node == parent)
         {
             return true;
         }
         
         node = node.parentNode;	
     }
     return false;
}


function displayUserOptions(event)
{
	var doc = document;
	var toggle = doc.getElementById('user_options_toggle');
	var header = doc.getElementById('header');
	var flyout = doc.getElementById('user_options_flyout');
	var overlay = doc.getElementById('modal_overlay');
	
	
	displayModalOverlay('transparent');
    header.bindEvent('click', hideUserOptions, false);
	overlay.bindEvent('click', hideUserOptions, false);
    overlay.classList.add('special');

	flyout.style.visibility = 'hidden';
	flyout.style.display = "block";
	flyout.style.marginLeft = '-' + (flyout.clientWidth - toggle.clientWidth) + 'px';//'-' + (contextPicker.clientWidth - 70)+ 'px';
	flyout.style.visibility = 'visible';
							
	flyout.style.display = 'block';
};

function hideUserOptions(event)
{
    if (event) {
        if (event.target.parentNode.id == 'user_options_toggle' || event.target.parentNode.id == 'user_options_toggle') {
            return false;
        }
        stopEventPropogation(event);
    }

	var doc = document;
	var overlay = doc.getElementById('modal_overlay');
	var flyout = doc.getElementById('user_options_flyout');
	
	overlay.unbindEvent('click', hideUserOptions, false);
	hideModalOverlay();
	
	flyout.setAttribute('style', '');
};

function displayFlyoutInModalBody(id, backgroundid)
{
    var flyout = document.getElementById(id);
    flyout.style.display = "block";

    var background = document.getElementById(backgroundid);
    background.style.height = "100%";
    background.style.width = "100%";

    var offset = flyout.offsetTop;
    flyout.style.top = (offset - document.getElementById('modal_body').scrollTop) + 'px';
    
};

function hideFlyoutInModalBody(id, backgroundid)
{
    var flyout = document.getElementById(id);
    var offset = flyout.offsetTop;
    flyout.style.top = (offset + document.getElementById('modal_body').scrollTop) + 'px';
    flyout.style.display = "none";

    var background = document.getElementById(backgroundid);
    background.style.height = "0px";
    background.style.width = "0px";

};

function isAlphabet(string)
{
    var alphaExp = /^[a-zA-Z]+$/;
    if(string.match(alphaExp))
    {
        return true;
    }
    else
    {
        return false;
    }
};

function getFooterLinksHtml()
{
	var html = '';
        html += '<a href="http://www.appigo.com" target="_blank">' + sprintf(labels.footer_copy, new Date().getFullYear()) + '</a> - ';
        html += '<a href="http://www.todo-cloud.com/terms/" target="_blank">' + labels.terms_of_service + '</a> - ';
        html += '<a href="http://www.todo-cloud.com/privacy/" target="_blank">' + labels.privacy_policy + '</a> - ';
		html += '<a href="http://support.appigo.com/" target="_blank">' + settingStrings.support + '</a>';
	
	return html;
};


function displayAboutModal(event)
{
	hideUserOptions();
	stopEventPropogation(event);
	
	var doc = document;
	var version = doc.getElementById('todopro_version').value;
	var revision = doc.getElementById('todopro_revision').value;
	
	var bodyHTML  = '<div class="text-center about-us-popup-wrapper">';
		bodyHTML += '<div class="app_logo container"></div>';
		//bodyHTML += '<p style="font-weight:bold">Todo Cloud</p>';
        bodyHTML += '<p style="font-weight:bold">' + labels.version + ' ' + version + '.' + revision + '</p>';
		bodyHTML += '<div class="breath-10"></div>';
		bodyHTML += '<p>'+labels.todo_cloud_gives_you_power_to_accomplish +'</p>';
		bodyHTML += '<p>' + labels.todo_cloud_is_designed_and_developed_in_utah + '</p>';
		bodyHTML += '<p>' + sprintf(labels.footer_copy, new Date().getFullYear()) + '</div>';
		bodyHTML += '</div>';
		
	var headerHTML = labels.todo_cloud ;
	var footerHTML = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
	var customBgColor = 'rgba(0,0,0,.3)';
	
	displayModalContainer(bodyHTML, null, footerHTML, customBgColor);
};


window.onorientationchange = catchOrientationChange;

//this should only be used on mobile devices
function catchOrientationChange()
{
	try 
	{
		var doc = document;
		var videoEl = doc.getElementById('video_iframe_wrap');
	
			videoEl.style.height = (videoEl.clientWidth / 1.78571428571429) + 'px';
	}
	catch(err)
	{}
};

function isValidEmailAddress(email)
{
	var regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    
    return regex.test(email);
};
function isValidURL(url) {
    var splited_url = url.split("?");
    var regex = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;

    return regex.test(splited_url[0]);
};
function isValidIP(ip) {
    var regex = /^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/g;
    return regex.test(ip);
};
function isValidURLIP(ip) {
    var regex = /((www\.)+[a-zA-Z0-9\.\-\_]+(\.[a-zA-Z]{2,3})+)|(\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b)/g;
    return regex.test(ip);
};

//Returns a string with the amount formatted as currency
function currencyFormatted(amount)
{
	var i = parseFloat(amount);
	if(isNaN(i)) { i = 0.00; }
	var minus = '';
	if(i < 0) { minus = '-'; }
	i = Math.abs(i);
	i = parseInt((i + .005) * 100);
	i = i / 100;
	s = new String(i);
	if(s.indexOf('.') < 0) { s += '.00'; }
	if(s.indexOf('.') == (s.length - 2)) { s += '0'; }
	s = minus + s;
	return s;
};

function clearNewFeatureFlagIfNeeded(flag, inputId)
{
    //if the value of the input is 1, we need to clear the flag so it doesn't get shown any more
    if(document.getElementById(inputId))
    {
        if(document.getElementById(inputId).value > 0)
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
                             
                        if(response.error)
                        {
                            if(response.error == "authentication")
                            {
                                history.go(0);
                            }
                        }
                    }
                    catch(e)
                    {
                    }
                }
            }
                             
             var params = 'method=updateUserSettings&new_feature_flags=' + flag;
             ajaxRequest.open("POST", "." , false);
             //Send the proper header information along with the request
             ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
             ajaxRequest.send(params);
         }
     }
};

function showFeatureVoteOptions()
{
	var headerHTML = labels.vote_for_upcoming_features ;
    var bodyHTML = '<p style="width:440px;margin-top:0">' + labels.wed_like_your_feedback_about + '</p>';
        bodyHTML += '<p style="width:440px">' + sprintf(labels.you_can_see_all_of_the_features_we, '<a class="action_link" style="font-weight:bold;text-decoration:underline" href="http://help.appigo.com/forums/21650303-ideas-under-consideration" target="_blank">', '</a>') + '</p>';

	var footerHTML = '<div class="button" onclick="hideModalContainer()">' + labels.ok + '</div>';
	
	displayModalContainer(bodyHTML, headerHTML, footerHTML);
	
};


String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

//Takes a text input element and removes any non-numeric characters
//and makes sure the value is not less than minValue or greater than maxValue
 function formatPositiveInteger(input, minValue, maxValue)
 {
     var value = input.value;
     
     //replace all non-numeric characters with an empty string
     value =  value.replace(/[^0-9]/g, '');
     
     if(value.length > 0)
     {
         var num = parseInt(value);
         
         if(num > maxValue)
            value = maxValue.toString();
         else if(num < minValue)
            value = minValue.toString();
     }
     
     if(value != input.value)
        input.value = value;
 };
 
 
function s4() 
{
  return Math.floor((1 + Math.random()) * 0x10000)
             .toString(16)
             .substring(1);
};

function guid() 
{
  return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
         s4() + '-' + s4() + s4() + s4();
}
function isDark(color) {
    var match = /(\d+).*?(\d+).*?(\d+)/.exec(color);
    if(match) {
        var r = parseInt(match[1]);
        var g = parseInt(match[2]);
        var b = parseInt(match[3]);
        var yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 170);
    }
    return false;
}

serialize = function (obj, prefix) {
    var str = [];
    for (var p in obj) {
        var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
        str.push(typeof v === "object" ?
            serialize(v, k) :
        encodeURIComponent(k) + "=" + encodeURIComponent(v));
    }
    return str.join("&");
};


/**
 * Global controllers
 */
function notify(data) {
    if (typeof data.error !== 'undefined') {
        var origin_message = data.error;
    } else {
        var origin_message = data.message;
    }
    var message = '';

    if (typeof origin_message === 'object') {
        if (Object.keys(origin_message).length > 0) {
            jQuery.each(origin_message, function (index, value) {
                message += value + '<br/>';
            });
        }
    } else {
        message = origin_message;
    }
    if (data.success) {
        new Messenger().post({
            message: message,
            type: 'success',
            showCloseButton: true
        });
    } else {
        new Messenger().post({
            message: message,
            type: 'error',
            showCloseButton: true
        });
    }
}

function newTaskPriority(prev_task_priority, next_task_priority) {
    if (typeof prev_task_priority != 'undefined' && typeof next_task_priority != 'undefined') {
        if (prev_task_priority == next_task_priority) {
            return prev_task_priority;
        }
        if (prev_task_priority == 0) {
            return 0
        }
        if (prev_task_priority == 9 || prev_task_priority == 5) {
            return next_task_priority;
        }
        if (prev_task_priority == 1) {
            if (next_task_priority == 9 || next_task_priority == 0) {
                return 5;
            }
            return prev_task_priority;
        }
    }
    //no tasks above. task moved to top of the list
    if (typeof prev_task_priority == 'undefined') {
        return next_task_priority;

        /*if (typeof next_task_priority != 'undefined') {
            if (next_task_priority == 1) {
                return next_task_priority;
            }
            if (next_task_priority == 5) {
                return 1;
            }
            if (next_task_priority == 9) {
                return 5;
            }
            if (next_task_priority == 0) {
                return 9;
            }
        }
        return false;*/
    }
    //no tasks below. task moved to bottom of the list
    if (typeof next_task_priority == 'undefined') {
        if (typeof prev_task_priority != 'undefined') {
            if (prev_task_priority == 1) {
                return 5;
            }
            if (prev_task_priority == 5) {
                return 9;
            }
            if (prev_task_priority == 9 || prev_task_priority == 0) {
                return 0;
            }
        }
        return false;
    }
}

function validateForm(form) {
    var success = true;
    var inputs = jQuery('input:not([type="hidden"]), select, textarea', form);
    inputs.each(function () {
        var input = jQuery(this);
        var label = false;
        var error = false;
        if (jQuery('label[for="' + input.attr('id') + '"]', form).size()) {
            label = jQuery('label[for="' + input.attr('id') + '"]', form);
        } else {
            if (input.parents('label').size()) {
                label = input.parents('label');
            }
        }
        if (input.is("textarea") && input.hasClass('required')) {
            if (input.val().trim() === '') {
                error = true;
            }
            if(input.hasClass('email')){
                var lines = input.val().split('\n');
                for (var i = 0; i < lines.length; i++) {
                    if (!isValidEmailAddress(lines[i].trim())) {
                        error = true;
                        break;
                    }
                }
            }
        }
        if (!input.is("textarea") && (input.hasClass('email') || input.attr('type') === 'email')) {
            if (!isValidEmailAddress(input.val().trim())) {
                error = true;
            }
        }

        if (error) {
            input.addClass('error');
            if (label) {
                label.addClass('error');
            }
            success = !error;
        } else {
            input.removeClass('error');
            if (label) {
                label.removeClass('error');
            }
        }
    });
    return success;
}
function showUserMessage() {
    var modal_content_source = jQuery('.welcome-message');
    var message_key = modal_content_source.data('message-name');
    var modal_header_content = jQuery('.modal-header', modal_content_source).html();
    var modal_content = jQuery('.modal-content', modal_content_source).html();
    var modal_footer_content = jQuery('.modal-footer', modal_content_source).html();
    setTimeout(function () {
        displayModalContainer(modal_content, modal_header_content, modal_footer_content);
    }, 0);
    if (message_key) {
        jQuery.ajax({
            type: 'POST',
            url: window.location.origin,
            data: serialize({method: 'updateUserMessage', message_key: message_key})
        });
    }
}

function removeTextSelection() {
    if (window.getSelection) {
        if (window.getSelection().empty) {  // Chrome
            window.getSelection().empty();
        } else if (window.getSelection().removeAllRanges) {  // Firefox
            window.getSelection().removeAllRanges();
        }
    } else if (document.selection) {  // IE?
        document.selection.empty();
    }
}
function getInternetExplorerVersion()
{
    var rv = -1;
    if (navigator.appName == 'Microsoft Internet Explorer')
    {
        var ua = navigator.userAgent;
        var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat( RegExp.$1 );
    }
    else if (navigator.appName == 'Netscape')
    {
        var ua = navigator.userAgent;
        var re  = new RegExp("Trident/.*rv:([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat( RegExp.$1 );
    }
    return rv;
}
jQuery(window).load(function(){
    if(jQuery('.progress-bar').size()){
        jQuery('.progress-bar').each(function(){
            var bar = jQuery('.bar', this);
            bar.css('width', bar.data('bar-width') + '%');
        });
    }
    if (typeof info_message_text !== 'undefined' && info_message_text !== '') {
        if (info_message_type == 'error') {
            displayGlobalErrorMessage(info_message_text);
        }
        else if (info_message_type == 'warning') {
            displayGlobalMessage(info_message_text);
        } else if (info_message_type == 'success') {
            displayGlobalMessage(info_message_text, true);
        }
    }
});
jQuery(document).ready(function () {
    if (jQuery().progressInitialize) {
        jQuery('.progress-button').progressInitialize();
    }
    Messenger.options = {
        extraClasses: 'messenger-fixed messenger-on-top messenger-on-right',
        theme: 'air'
    };
    jQuery(document).on('click', '.form-action .btn-form-submit', function (e) {
        e.stopImmediatePropagation();
        var btn = jQuery(this);
        var form = btn.parents('.form-action');

        if(!validateForm(form)){
            return false;
        } else {
            var form_wrapper = jQuery(this).parents('.form-wrapper');
            var url = form.attr('action');
            var method = form.attr('method');
            var show_progress = form.data('progress');

            if (url === '#') {
                url = window.location.origin;
            }

            if (typeof (method) === 'undefined') {
                method = 'POST';
            }
            if (typeof (show_progress) === 'undefined' || show_progress === 'false') {
                show_progress = false;
            } else {
                show_progress = true;
            }

            var data = {};
            var progress_interval = '';
            jQuery.ajax({
                type: method,
                dataType: 'json',
                url: url,
                data: form.serialize(),
                beforeSend: function () {
                    if (show_progress) {
                        btn.progressSet(20);
                        progress_interval = setInterval(function () {
                            btn.progressIncrement();
                        }, 1000);
                    }
                },
                success: function (json) {
                    if (json.success) {
                        if (show_progress) {
                            setTimeout(function () {
                                btn.removeClass('finished');
                            }, 3000);
                        }
                    } else {
                        if (show_progress) {
                            btn.html('Error');
                        }
                    }
                    if (show_progress) {
                        clearInterval(progress_interval);
                        btn.progressFinish();
                    }
                },
                error: function () {
                    displayGlobalErrorMessage('Unknown Response');
                    if (show_progress) {
                        clearInterval(progress_interval);
                        btn.progressFinish();
                    }
                }
            });

            if (form.data('reload-type') === 'live') {
                return false;
            }
        }
    })
    ;
    jQuery('#modal_container')
        .on('click', '.btn-cancel-modal', hideModalContainer);

    if (jQuery('.welcome-message').size() > 0) {
        showUserMessage();
    }

    if (getInternetExplorerVersion() >= 11) {
        jQuery(document).on('click', '[id^="insert_inside_target_"]', function (event) {
            var item = jQuery(this);
            selectTask(item.parents('.task_wrap').attr('id'), event);
        });
    }
});
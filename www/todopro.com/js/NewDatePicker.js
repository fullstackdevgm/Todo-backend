var datepicker = {};

datepicker.unix = null;
datepicker.year = null;
datepicker.month = null;
datepicker.day = null;
datepicker.ownerId = null;
datepicker.hasTime = null;
datepicker.hours = null;
datepicker.minutes = null;
datepicker.dateString = null;
datepicker.timeString = null;
datepicker.isMilitary = null;

//builds datepicker UI inside elementId html element
function buildDatepickerUI(elementId, selectedUnixDate, displayCustomButtons)//, saveDateJSFunction, saveDateJSFunctionParam)
{
	//saveDateJSFunction = saveDateJSFunction || '';

	var doc = document;
	var calendarHTML =  '';
	var customButtonsHTML = '';
	var sDate = {};
	var highlightDate = false;

	if(selectedUnixDate) //set up UI and show selected date
	{
		if (selectedUnixDate == 0) //set up UI to display today's date without selecting it
		{
			sDate = new Date();
		}
		else //display UI and display selected date
		{
			highlightDate = true;
			sDate = new Date(selectedUnixDate * 1000);
		}

		datepicker.ownerId = elementId;
		datepicker.unix = selectedUnixDate;
		datepicker.year = sDate.getFullYear();
		datepicker.month = sDate.getMonth() + 1; //javascript starts counting months at 1 not 0
		datepicker.day = sDate.getDate();
	}

	//set up days of week headers
	var daysHeaderHTML = '';

	for (var i = 0; i < daysInWeek; i++)
		daysHeaderHTML += '<li>'+ dateStrings.days[i].slice(0,2) + '</li>';

	//set up calendar for selected month
	var aDay = datepicker.day;
	var aMonth = datepicker.month;
	var aYear = datepicker.year;
	var dayOffset = 999;
	//var daysInMonthHTML = selectDateInUI ? getMonthCalendarHTML(aMonth, aYear, aDay, aMonth, aYear) : getMonthCalendarHTML(aMonth, aYear);

	if (displayCustomButtons)
	{
		customButtonsHTML += getCustomButtonsHTML(elementId);
	}

	calendarHTML +=	'	<div id="dateStringDisplay">';
	calendarHTML +=	'		<input type="text" id="datePickerStringDisplay_' + elementId + '" readonly="readonly"/>';
	calendarHTML +=	'	</div>';
	calendarHTML +=	'   <div id="calendar">';
	calendarHTML +=	'       <div id="monthYearControl_' + elementId + '" class="monthYearControl">';
	calendarHTML += 			getMonthNavigationHTML(aMonth, aYear, elementId);
	calendarHTML +=	'       </div>';
	calendarHTML +=	'       <div id="daysOfWeekWrapper">';
	calendarHTML +=	'       	<ul id="daysOfWeek">';
	calendarHTML +=					daysHeaderHTML;
	calendarHTML +=	'       	</ul>';
	calendarHTML +=	'       </div>';
	calendarHTML +=	'       <div id="daysOfMonth_' + elementId + '" class="daysOfMonth">';
	calendarHTML += 			getMonthCalendarHTML(aMonth, aYear, highlightDate);
	calendarHTML +=	'       </div>';
	calendarHTML +=	'   </div>';
	calendarHTML += customButtonsHTML;

	doc.getElementById(elementId).innerHTML = calendarHTML;
	//displayDateInElement();
};

function getCustomButtonsHTML(elementId)
{
	var today = new Date();
	today = today.getTime() / 1000;

	var tomorrow = new Date();
	tomorrow.setDate(tomorrow.getDate() + 1);
	//tomorrow.setDate(tomorrow.getDate() + 1);

	tomorrow = tomorrow.getTime() / 1000;

	var nextWeek = new Date();
	nextWeek.setDate(nextWeek.getDate() + 7);
	nextWeek = nextWeek.getTime() / 1000;

	var nextMonth = new Date();
	nextMonth.setMonth(nextMonth.getMonth() + 1);
	nextMonth = nextMonth.getTime() / 1000;

	//todayButton.onclick = function(){setupCalendar(calendarId, today.getFullYear(), (today.getMonth() + 1), today.getDate(), null, null, null, true);};
	//tomorrowButton.onclick = function(){setupCalendar(calendarId, tomorrow.getFullYear(), (tomorrow.getMonth() + 1), tomorrow.getDate(), null, null, null, true);};
	//nextWeekButton.onclick = function(){setupCalendar(calendarId, nextWeek.getFullYear(), (nextWeek.getMonth() + 1), nextWeek.getDate(), null, null, null, true);};
	//nextMonthButton.onclick = function(){setupCalendar(calendarId, nextMonth.getFullYear(), (nextMonth.getMonth() + 1), nextMonth.getDate(), null, null, null, true);};
	//noneButton.onclick = function(){clearDateAndTime();};
	var html = '';
	html += '<div id="customButtons">';
	html +=	'    <ul>';
    html += '    	<li><div class="button" id="todayButton_' + elementId + '" onclick="saveDate(' + today + ')">' + taskSectionsStrings.today + '</div></li>';
    html += '    	<li><div class="button" id="tomorrowButton_' + elementId + '" onclick="saveDate(' + tomorrow + ')">' + taskSectionsStrings.tomorrow + '</div></li>';
    html += '    	<li><div class="button" id="nextWeekButton_' + elementId + '" onclick="saveDate(' + nextWeek + ')">' + taskSectionsStrings.nextweek + '</div></li>';
	html +=	'    	<li><div class="button" id="nextMonthButton_' + elementId + '" onclick="saveDate(' + nextMonth + ')">' + taskSectionsStrings.nextmonth + '</div></li>';
	html +=	'    	<li><div class="button" id="noneButton_' + elementId + '" onclick="clearDueDateInDatePicker()">' + taskSectionsStrings.none + '</div></li>';
	html +=	'    </ul>';
	html +=	'</div>';

	return html;
};

function clearDueDateInDatePicker()
{
	try
	{
		document.getElementById('selectedDate').removeAttribute('id');
	}
	catch(err){}

	datepicker.unix = 0;
	datepicker.day = null;
	datepicker.month = null;
	datepicker.year = null;

	displayDateInElement();
};


function getMonthCalendarHTML(month, year, highlightDate)//, saveDateJSFunction, saveDateJSFunctionParam)
{
	//saveDateJSFunction = saveDateJSFunction || '';
	var daysInMonthHTML = '';
	var daysInWeek = 7; //monday thru sunday
	var maxWeeksInMonth = 6;
	var dayCounter = 0;
	var weekCounter = 0;
	var dayInMonth = 0;
	var dayOffset = 999;
	var firstDayOfMonth = firstDayInMonth(month, year);
	var numberOfDays = daysInMonth(month, year);
	var numberOfDaysPrevMonth = daysInMonth(month - 1, year);
	var dayCounterNextMonth = 1;
	
	var savedDate = datepicker;

	var selectedMonth = savedDate.month;
	var selectedYear = savedDate.year;
	var selectedDay = savedDate.day;
	var selectedDateId = '';
	
	var unixDate = null;

	for (var i = 1; i <= maxWeeksInMonth; i++) //iterate through weeks
	{
		weekCounter++;
		//if (dayInMonth < numberOfDays)
		{
			var unixTodayDate = new Date();
			var todayDay = unixTodayDate.getDate();
			var todayMonth = unixTodayDate.getMonth() + 1;
			var todayYear = unixTodayDate.getFullYear();
			var todayDateClass = '';
			
			daysInMonthHTML += '<ul id="week' + i + '">';

			for (var a = 0; a < daysInWeek; a++) //iterate through days
			{
				if(dayCounter < firstDayOfMonth)
				{
					var prevDayInMonth = numberOfDaysPrevMonth - firstDayOfMonth + a + 1;
					unixDate = (new Date(year, month - 2, prevDayInMonth)).getTime()/1000;
					daysInMonthHTML += '<li class="otherMonthDay" ondrop="dateCatchDrop(event, \'' + unixDate + '\')" ondragenter="return false" ondragover="return false" onclick="saveDate(\'' + unixDate + '\')">' + prevDayInMonth + '</li>';
					
				}
				else if (dayCounter < numberOfDays + firstDayOfMonth )
				{
					if (dayOffset == 999)
						dayOffset = dayCounter - 1;

					dayInMonth = dayCounter - dayOffset;
					unixDate = (new Date(year, month - 1, dayInMonth)).getTime()/1000;
					

					if (highlightDate && savedDate.unix != null && selectedMonth == month && selectedYear == year && selectedDay == dayInMonth)
						selectedDateId = ' id="selectedDate" ';
					if (month == todayMonth && dayInMonth == todayDay && year == todayYear)
						todayDateClass = ' class="todayDate" ';		

					daysInMonthHTML += '<li ' + todayDateClass + selectedDateId + ' onclick="saveDate(\'' + unixDate + '\')" ondrop="dateCatchDrop(event, \'' + unixDate + '\')" ondragenter="return false" ondragover="return false">' + dayInMonth + '</li>';
				}
				else 
				{
					unixDate = (new Date(year, month, dayCounterNextMonth)).getTime()/1000;
					daysInMonthHTML += '<li class="otherMonthDay" onclick="saveDate(\'' + unixDate + '\')" ondrop="dateCatchDrop(event, \'' + unixDate + '\')" ondragenter="return false" ondragover="return false">' + dayCounterNextMonth + '</li>';
					dayCounterNextMonth++;
				}
				
				dayCounter++;
				selectedDateId = '';
				todayDateClass = '';
			}
			
			daysInMonthHTML += '</ul>';
			
		}
	}

	return daysInMonthHTML;//'<ul><li>1</li><li>2</li><li>3</li><li>4</li></ul>'; //daysInMonthHTML;
};

function getMonthNavigationHTML(month, year, datepickerId)
{
	month = parseInt(month, 10);
	year = parseInt(year, 10);

	var navHTML = '';
	var nextMonth = '';
	var nextYear = '';
	var prevMonth = '';
	var prevYear = '';

	if (month < 12)
	{
		nextMonth = month + 1;
		nextYear = year;
	}
	else
	{
		nextMonth = 1;
		nextYear = year + 1;
	}

	if(month > 1)
	{
		prevMonth = month - 1;
		prevYear = year;
	}
	else
	{
		prevMonth = 12;
		prevYear = year - 1;
	}

	navHTML += '<span id="prevMonth" onclick="loadMonthCalendar(event, \'' + prevMonth + '\', \'' + prevYear + '\')"><</span>';
	navHTML += '<span id="curMonth">';
	navHTML += 	    dateStrings.months[month] + " " + year;
	navHTML += '</span>';
	navHTML += '<span id="nextMonth" onclick="loadMonthCalendar(event, \'' + nextMonth + '\', \'' + nextYear + '\')">></span>';

	return navHTML;
};

function loadMonthCalendar(event, month, year)
{
	if(event)
		stopEventPropogation(event);
		

		var doc = document;
		var monthEl = doc.getElementById('daysOfMonth_' + datepicker.ownerId);
		var monthNavEl = doc.getElementById('monthYearControl_' + datepicker.ownerId);
	
		monthEl.innerHTML = getMonthCalendarHTML(month, year, true);
		monthNavEl.innerHTML = getMonthNavigationHTML(month, year);

};

function saveDate(unixDate)//, JSFunction, JSFunctionParam)
{
	//setTimeout(function(){
	var aDate = new Date(unixDate * 1000);

	datepicker.unix = unixDate;
	datepicker.year = aDate.getFullYear();
	datepicker.month = aDate.getMonth() + 1;
	datepicker.day = aDate.getDate();

	//loadMonthCalendar(aDate.getMonth() + 1, aDate.getFullYear(), datepicker.ownerId);
	//displayDateInElement();
//	if (JSFunction && JSFunction != '')
//		JSFunction(JSFunctionParam);


	//console.log('saved date: ' + datepicker.month + '.' + datepicker.day + '.' + datepicker.year);
	//}, 100);
};

function displayDateInElement(elementId)
{
	
	var doc = document;
	var sDate = datepicker;
	var day = sDate.day;
	var month = sDate.month;
	var year = sDate.year;
	
	elementId = elementId || 'datePickerStringDisplay_' + sDate.ownerId;
	
	var displayEl = doc.getElementById(elementId);
	var displayString = '';
	if (sDate.unix != 0)
	{
		displayString = displayHumanReadableDate(sDate.unix, false, true, true);
	}
	else
	{
		displayString = taskStrings.noDate;
	}
	

	if (elementId)
		displayEl.innerHTML = displayString;
	else	
		displayEl.value = displayString;

	
	datepicker.dateString = displayString;
};


//saves unixtime to elementId html element
function saveDateToHtmlElement(elementId)
{
	document.getElementById(elementId).value = datepickerDate;
};

/*utils*/

//daysInMonth functions
//purpose: returns the number of days in a month
//parameters: a month (int), a 4-digit year (int)
function daysInMonth(month,year)
{
    return new Date(year, month, 0).getDate();
};


//firstDayInMonth function
//purpose: returns the int value of the day of the week of the first day of the month. Sun = 0, Mon = 1, etc
//parameters: a month (int), a 4-digit year (int)
function firstDayInMonth(month, year)
{
	return new Date(year, month - 1, 1).getDay();
};
var calendarId = '';

var datePickerData = '';

var firstDayOfWeek = '';
var language = '';
var daysInWeek = 7;
var maxWeeksInMonth = 6;

var militaryTime = 24;
var timeFormat = 12;//militaryTime;  //12 for am/pm - 24 for military time

var languages = ["en"];
var today = new Date();

//days full name languages
var daysFullEnglish = daysOfWeekStrings;
var daysFullStrings = [daysFullEnglish];

//months languages
var monthsEnglish = monthsStrings;
var monthStrings = [monthsEnglish];

//'Time' label languages
var timeLabelEnglish = [labels.time];
var timeLabelStrings = [timeLabelEnglish];

//custom butons languages
var buttonsEnglish = [taskSectionsStrings.today, taskSectionsStrings.tomorrow, taskSectionsStrings.nextweek, taskSectionsStrings.nextmonth, taskSectionsStrings.none];
var buttonStrings = [buttonsEnglish];

//set language
//try {language = document.getElementById('lang').value;}
//catch(err){}

if(language == '')
	language = "en"; //en:English, sp:Spanish, po:Portuguese

var userLang = languages.indexOf(language);//selected language

//set up date picker when js is loaded
this.onload = function()
{
	//drawCalendarStructure('datePicker');
	//setupCalendar('datePicker', null, null, null, null, null, null, false);
};

//drawCalendarStructure function
//purpose: sets up the html structure of the date picker
//parameteres: the id of the div container where the date picker will be layed out
//note: --
function drawCalendarStructure(containerId)
{
	var calendarContainer = document.getElementById(containerId);
	var meridianDisabled = '';
	if (timeFormat == militaryTime)
		meridianDisabled = ' disabled="disabled" ';

	var calendarHTML =  '	<div id="dateStringDisplay">';
	calendarHTML +=		'		<input type="text" id="datePickerStringDisplay" readonly="readonly"/>';
	calendarHTML +=		'	</div>';
	calendarHTML +=		'   <div id="calendar">';
	calendarHTML +=		'       <div id="monthYearControl">';
	calendarHTML +=		'       	<span id="prevMonth"><</span>';
	calendarHTML +=		'       	<span id="curMonth"></span>';
	calendarHTML +=		'       	<span id="nextMonth">></span>';
	calendarHTML +=		'       </div>';
	calendarHTML +=		'       <div id="daysOfWeekWrapper">';
	calendarHTML +=		'       	<ul id="daysOfWeek">';
	calendarHTML +=		'       	</ul>';
	calendarHTML +=		'       </div>';
	calendarHTML +=		'       <div id="daysOfMonth">';
	calendarHTML +=		'       </div>';
	calendarHTML +=		'   </div>';
	calendarHTML +=		'   <div id="time">';
	calendarHTML +=		'       <span id="timeLabel"></span>';
	calendarHTML +=		'       <input id="hourPicker" maxlength="2" type="text" />';
	calendarHTML +=		'       <span>:</span>';
	calendarHTML +=		'       <input id="minutesPicker" maxlength="2" type="text" />';
	calendarHTML +=		'       <input id="meridianPicker" maxlength="2" type="text"' + meridianDisabled + '/>';
	calendarHTML +=		'       <img id="clearTime" src="https://s3.amazonaws.com/static.plunkboard.com/images/calendar_picker/time_clear.png"/>';
	calendarHTML +=		'   </div>';
	calendarHTML +=		'   <div id="customButtons">';
	calendarHTML +=		'       <ul>';
	calendarHTML +=		'       	<li><div id="todayButton">' + taskSectionsStrings.today + '</div></li>';
	calendarHTML +=		'       	<li><div id="tomorrowButton">' + taskSectionsStrings.tomorrow + '</div></li>';
	calendarHTML +=		'       	<li><div id="nextWeekButton">' + taskSectionsStrings.nextweek + '</div></li>';
	calendarHTML +=		'       	<li><div id="nextMonthButton">' + taskSectionsStrings.nextmonth + '</div></li>';
	calendarHTML +=		'       	<li><div id="noneButton">' + taskSectionsStrings.none + '</div></li>';
	calendarHTML +=		'       </ul>';
	calendarHTML +=		'       <ul>';
	calendarHTML +=		'       	<li><div onkeydown="saveDueDateBtnKeyDown()" id="saveDueDateBtn">' + labels.save + '</div></li>';
	calendarHTML +=		'       </ul>';
	calendarHTML +=		'   </div>';
	calendarHTML +=		'   <form id="datePickerSelection">';
	calendarHTML +=		'       <input type="hidden" name="year" value="00" />';
	calendarHTML +=		'       <input type="hidden" name="month" value="00" />';
	calendarHTML +=		'       <input type="hidden" name="day" value="00" />';
	calendarHTML +=		'       <input type="hidden" name="hour" value="00" />';
	calendarHTML +=		'       <input type="hidden" name="minutes" value="00" />';
	calendarHTML +=		'       <input type="hidden" name="meridian" value="--" />';
	calendarHTML +=		'   </form>';

	calendarContainer.innerHTML = calendarHTML;

	document.getElementById(containerId).style.display = 'none';

	datePickerData = document.getElementById('datePickerSelection');
};

//displaySelectedDate function
//purpose: displays a formatted human readable version of the date and time values stored in the datePickerSelection form
//parameters: none
//note: this should be called everytime there is a change to the datePickerSelection values
function displaySelectedDate()
{
	var doc = document;

	//get values from datePickerSelection form
	var year = datePickerData.year.value;
	var month = datePickerData.month.value;
	var day = datePickerData.day.value;
	var hour = datePickerData.hour.value;
	var minutes = datePickerData.minutes.value;
	var meridian = datePickerData.meridian.value;

	var dayString = daysFullStrings[userLang][(new Date(year, month - 1, day)).getDay()].substr(0,3);

	//check for null values
	if (hour == "")
		hour = 0;
	if (minutes == "")
		minutes = 0;
	if (meridian == "")
		meridian = "--";

	//set up string to be displayed
	var hourString = addLeadingZero(hour);
	var minutesString = addLeadingZero(minutes);
	var timeString = '';
	var dateString = '';

	//nothing will be displayed in the textfield when no date is set
	if(year == '0' & month == '0' && day =='0')
		dateString = '';
	else
		dateString = dayString +', ' + monthStrings[userLang][month -1] + " " + day;// + ', ' + year;

	//nothing will be displayed in the textfield when no time is set
	if ((hourString == '00' && minutesString == '00' && meridian =='--') || (hour == "" && minutes == "" && meridian ==""))
	{
		timeString = '';
	}
	else
	{
		timeString = ' @ ' + hourString + ':' + minutesString;

		if (timeFormat != militaryTime && hourString != '00')// && minutesString != '00')
		timeString += ' ' + meridian;
	}

	//output formated date and time string
	doc.getElementById('datePickerStringDisplay').value = dateString + timeString;

	//set up time picker UI
	doc.getElementById('hourPicker').value = hourString;
	doc.getElementById('minutesPicker').value = minutesString;

	if (timeFormat != militaryTime)
		doc.getElementById('meridianPicker').value = meridian;
};

//setup Calendar function
//purpose: sets up the month and year header, the days header, the custom buttons, and the time picker
//parameters: calendarId, a year, a month, a day, an hour, minutes, meridian, and a true/false value that indicates if the passed date needs to be displayed in the datePickerStringDisplay textfield and time picker and if the date needs to be visually 'selected' in the calendar
function setupCalendar(calId, aYear, aMonth, aDay, aHour, aMinutes, meridian, displayDate)
{
	var doc = document;

    calId = String(calId);
	calendarId = calId;

	aMonth = parseInt(aMonth);

	//check for null parameters
	if(aDay == '')
		aDay = new Date().getDate();
	if(aMonth == '')
		aMonth = new Date().getMonth() + 1;
	if(aYear == '')
		aYear = new Date().getFullYear();

	//check for meridian when am/pm time format is being used
	if(meridian == '' || meridian == null)
	{
		meridian = datePickerData.meridian.value;
	}

	//check for hour boundaries when am/pm time format is being used
	if(aHour == '')
	{
		if (parseInt(datePickerData.hour.value, 10) == 0)
		{
			aHour = datePickerData.hour.value;
		}
		else
		{
			aHour = 0;
		}
	}

	//check for minutes values
	if(aMinutes == '')
	{
		if(parseInt(datePickerData.hour.value, 10) == 0 && parseInt(datePickerData.minutes.value, 10) == 0)
			aMinutes = datePickerData.minutes.value;
		else
			aMinutes = 0;
	}

	//save selected values
	saveAndSelectDate(aYear, aMonth, aDay);
	saveAndSelectTime(aHour, aMinutes, meridian);


	//set up month and year header
	doc.getElementById('curMonth').innerHTML = monthStrings[userLang][aMonth - 1] + " " + aYear;


	//set days of week header
	var daysHeaderHTML = '';

	for (var i = 0; i < daysInWeek; i++)
		daysHeaderHTML += '<li>'+ daysFullStrings[userLang][i].slice(0,2) + '</li>';

	doc.getElementById('daysOfWeek').innerHTML = daysHeaderHTML;


	//fill days in month
	var daysInMonthHTML = '';
	var firstDayOfMonth = firstDayInMonth(aMonth, aYear);
	var numberOfDays = daysInMonth(aMonth, aYear);
	var dayCounter = 0;
	var dayInMonth = 0;
	var dayOffset = 999;
	var selectedDateId = '';
	for (var i = 1; i <= maxWeeksInMonth; i++)
	{
		if (dayInMonth < numberOfDays)
		{
			daysInMonthHTML += '<ul id="week' + i + '">';

			for (var a = 0; a < daysInWeek; a++)
			{
				if(dayCounter < firstDayOfMonth || dayInMonth >= numberOfDays)
					daysInMonthHTML += '<li class="emptyDay"></li>';
				else
				{
					if (dayOffset == 999)
						dayOffset = dayCounter - 1;

					dayInMonth = dayCounter - dayOffset;
					if (displayDate && dayInMonth == datePickerData.day.value && aMonth == datePickerData.month.value && aYear == datePickerData.year.value)
						selectedDateId = 'id="selectedDate" ';

					daysInMonthHTML += '<li ' + selectedDateId + 'onclick="saveSelectAndHighlightDate(' + aYear + ', ' + aMonth + ', ' + dayInMonth + ', event)">' + dayInMonth + '</li>';
				}
				dayCounter++;
				selectedDateId = '';
			}

			daysInMonthHTML += '</ul>';
		}
	}

	doc.getElementById('daysOfMonth').innerHTML = daysInMonthHTML;


	//set up nextMonth button
	var anextMonth = '';
	var nextYear = '';
	if(aMonth < 12)
	{
		nxtMonth = aMonth + 1;
		nextYear = aYear;
	}
	else
	{
		nxtMonth = 1;
		nextYear = aYear + 1;
	}

	doc.getElementById('nextMonth').onclick = function(){setupCalendar(calendarId, nextYear, nxtMonth, aDay, aHour, aMinutes, meridian,false);};

	//set up prevMonth button
	var prvMonth = '';
	var prevYear = '';

	if(aMonth > 1)
	{
		prvMonth = aMonth - 1;
		prevYear = aYear;
	}
	else
	{
		prvMonth = 12;
		prevYear = aYear - 1;
	}

	doc.getElementById('prevMonth').onclick = function(){setupCalendar(calendarId, prevYear, prvMonth, aDay, aHour ,aMinutes ,meridian,false);};


	//set up time picker
	doc.getElementById('timeLabel').innerHTML = timeLabelStrings[userLang][0] + ': ';

	var hourPicker = doc.getElementById('hourPicker');
	var minutesPicker = doc.getElementById('minutesPicker');
	var meridianPicker = doc.getElementById('meridianPicker');

	hourPicker.onblur = function(){saveAndSelectTime();displaySelectedDate();};
	minutesPicker.onblur = function(){saveAndSelectTime();displaySelectedDate();};
	meridianPicker.onblur = function(){saveAndSelectTime();displaySelectedDate();};

	hourPicker.setAttribute('onkeydown', 'changeTimeValue(event)');
	minutesPicker.setAttribute('onkeydown', 'changeTimeValue(event)');
	meridianPicker.setAttribute('onkeydown', 'changeMeridianValue(event)');

	doc.getElementById('clearTime').onclick = function(){saveAndSelectTime("0","0","--");displaySelectedDate();};

	//set up custom buttons
	var tomorrow = new Date();
	tomorrow.setDate(tomorrow.getDate() + 1);

	var nextWeek = new Date();
	nextWeek.setDate(nextWeek.getDate() + 7);

	var nextMonth = new Date();
	nextMonth.setMonth(nextMonth.getMonth() + 1);

	var todayButton = doc.getElementById('todayButton');
	var tomorrowButton = doc.getElementById('tomorrowButton');
	var nextWeekButton = doc.getElementById('nextWeekButton');
	var nextMonthButton = doc.getElementById('nextMonthButton');
	var noneButton = doc.getElementById('noneButton');

	todayButton.innerHTML = buttonStrings[userLang][0];
	tomorrowButton.innerHTML = buttonStrings[userLang][1];
	nextWeekButton.innerHTML = buttonStrings[userLang][2];
	nextMonthButton.innerHTML = buttonStrings[userLang][3];
	noneButton.innerHTML = buttonStrings[userLang][4];

	todayButton.onclick = function(){setupCalendar(calendarId, today.getFullYear(), (today.getMonth() + 1), today.getDate(), null, null, null, true);};
	tomorrowButton.onclick = function(){setupCalendar(calendarId, tomorrow.getFullYear(), (tomorrow.getMonth() + 1), tomorrow.getDate(), null, null, null, true);};
	nextWeekButton.onclick = function(){setupCalendar(calendarId, nextWeek.getFullYear(), (nextWeek.getMonth() + 1), nextWeek.getDate(), null, null, null, true);};
	nextMonthButton.onclick = function(){setupCalendar(calendarId, nextMonth.getFullYear(), (nextMonth.getMonth() + 1), nextMonth.getDate(), null, null, null, true);};
	noneButton.onclick = function(){clearDateAndTime();};

	//display human readable date and time in textfield and in time picker
	if (displayDate)
		displaySelectedDate();
};


//saveAndSelectDate function
//purpose: saves the given parameters in the datePickerSelection form
//parameters: 4-digit year (int), month (int), day (int), a window event (this is necessary to highlight the selected date on the UI)
function saveAndSelectDate(year, month, day, e)
{
	//store values
	datePickerData.year.value = year;
	datePickerData.month.value = month;
	datePickerData.day.value = day;

	//update UI
	if (e)
	{
		//displaySelectedDate();
		if (e.target.getAttribute('id') != 'selectedDate')
		{
			try
			{
				document.getElementById('selectedDate').removeAttribute('id');
			}
			catch(err){}

			e.target.setAttribute('id', 'selectedDate');
		}
	}
};

//saveAndSelectTime function
//purpose: saves the given parameters in the datePickerSelection form
//parameters: hour (int), minutes (int), meridian (string): am/pm
function saveAndSelectTime(hour, minutes, meridian)
{
	var doc = document;
	//meridian
	if(!meridian)
	{
		if (doc.getElementById('meridianPicker').value != '--' && timeFormat != militaryTime)
		{
			if((datePickerData.hour.value != '00' || datePickerData.minutes.value != '00' ) && (datePickerData.meridian.value == '--' || datePickerData.meridian.value =='') && timeFormat != militaryTime)
			{
				meridian = 'am';
			}
			else
			{
				meridian = doc.getElementById('meridianPicker').value;
			}
		}
		meridian = doc.getElementById('meridianPicker').value;

		datePickerData.meridian.value  = meridian;
	}
	else
	{
		datePickerData.meridian.value = meridian;
	}

	//hour
	if(!hour)
		hour = doc.getElementById('hourPicker').value;

	if (timeFormat != militaryTime && (hour > 12 || hour == ''))
		hour = 12;
	else if (hour > 23 || hour =='')
		hour = 23
/*
	else if (hour == 12 && meridian == 'am')
		hour = 24;
*/
	else
		datePickerData.hour.value = hour;

	//minutes
	if(!minutes)
		minutes = doc.getElementById('minutesPicker').value;

	if (minutes > 59 || minutes =='')
		minutes = 00;
	else
		datePickerData.minutes.value = minutes;


	//time is selected and no date has been selected previously
	if(datePickerData.year.value == 0 && datePickerData.month.value == 0 && datePickerData.day.value == 0)
	{
		setupCalendar(calendarId, today.getFullYear(), today.getMonth() + 1, today.getDate(), hour, minutes, meridian, true);
	}
};

//changeTimeValue function
//purpose: increases or decreases value of input element when the up or down arrow keys are pressed
//parameters: a window event
function changeTimeValue(e)
{
	var doc = document;

	if (e.keyCode != 8) //if user is not deleting (backspace)
	{
		var inputEl = e.target;
		var inputElId = inputEl.getAttribute('id');
		var hourEl = doc.getElementById('hourPicker');
		var curValue = parseInt(inputEl.value, 10);

		//check for when there is nothing in the inputEl
		if (curValue/curValue != 1)
		{
			curValue = 0;
			inputElValue = '00';
		}

		if (e.keyCode == 38) //up arrow
		{
			//hour picker
			if(inputElId == 'hourPicker')
			{
				if (timeFormat == militaryTime && curValue + 1 > 23)
					curValue = -1;
				if (timeFormat != militaryTime && curValue + 1 > 12)
					curValue =	0;
			}

			//minutes picker
			if(inputElId == 'minutesPicker')
			{
				if (curValue + 1 > 59)
				{
					curValue = -1;

					//update hourPicker value +1
					var hourElVal = parseInt(hourEl.value, 10) + 1;

					if (timeFormat == militaryTime && hourElVal > 23)
						hourElVal = 0;
					else if(timeFormat != militaryTime && (hourElVal > 12 || hourElVal == 0))
						hourElVal = 1;

					hourEl.value = addLeadingZero(parseInt(hourElVal));
				}
				//if minutes are set and the hour is not set for am/pm time
				if (parseInt(hourEl.value, 10) == 0 && timeFormat != militaryTime)
					hourEl.value = "01";
			}

			//update current picker
			inputEl.value = addLeadingZero(curValue + 1);
		}
		if( e.keyCode == 40) //down arrow
		{
			//hour picker
			if(curValue - 1 < 1  && inputElId == 'hourPicker')
			{
				if (timeFormat == militaryTime)
					curValue = 24; //values are -1 at the end of if statement
				else
					curValue = 13; //values are -1 at the end of if statement
			}

			//minutes picker
			if (curValue - 1 < 1 && inputElId == 'minutesPicker')
			{
				curValue = 60;

				//update hourPicker value
				var hourElVal = parseInt(hourEl.value, 10) - 1;

				if (timeFormat == militaryTime)
				{
					if(hourElVal < 0)
						hourElVal = 23;
				}
				else
				{
					if(hourElVal < 1)
						hourElVal = 12;
				}

				hourEl.value = addLeadingZero(parseInt(hourElVal));
			}

			inputEl.value = addLeadingZero(curValue - 1);
		}

		//allow only number chars and other special keys in textfield
		if ( (e.keyCode < 96 || e.keyCode > 105) && (e.keyCode < 48 || e.keyCode > 57) && e.keyCode != 8 /*backspace*/ && e.keyCode != 9/*tab*/ && e.keyCode != 46 /*delete*/)
		{
			//console.log('keyCode: ' + e.keyCode);
			//changeMeridianValue(console.log('charCoe: ' + e.charCode);

			e.preventDefault();
		}
	}
};

//changeMeridianValue function
//purpose: switches merdian value between am/pm when the up or arrow keys are pressed
//parameters: a window event
function changeMeridianValue(e)
{
	var doc = document;

	if(timeFormat != militaryTime)
	{
		var inputEl = e.target;
		var inputElId = inputEl.getAttribute('id');
		var hourPickerEl = doc.getElementById('hourPicker');
		var minutesPickerEl = doc.getElementById('minutesPicker');
		var curValue = inputEl.value;

		//only allow key strokes for 'a', 'p', up and down
		if (e.keyCode == 65 || e.keyCode == 97) // a
		{
			inputEl.value = 'am';
		}
		else if (e.keyCode == 112 || e.keyCode == 80) // p
		{
			inputEl.value = 'pm';
		}
		else if (e.keyCode == 38 || e.keyCode == 40) //up and down keys
		{
			if (curValue == 'am' || curValue == '--')
			{
				inputEl.value = 'pm';
			}
			else
			{
				inputEl.value = 'am';
			}
		}
		else
			e.preventDefault();

		if (hourPickerEl.value == '00' && minutesPickerEl.value =="00")
			hourPickerEl.value = "12";
	}
};


//displayCalendarAndSelectDate function
//purpose: sets up the date picker and displays it
//parameters: window event, unix date (int)
function displayCalendarAndSelectDate(e, unixDate, taskId)
{
	var year, month, day, hour, minutes, meridian, displayDate;

	if (unixDate == 0)
	{
		year = 0;
		month = 0;
		day = 0;
		hour = 0;
		minutes = 0;
		meridian = "--";
		displayDate = false;
	}
	else
	{
		var selectedDate = new Date(unixDate * 1000);

		year = selectedDate.getFullYear();
		month = selectedDate.getMonth()+ 1;
		day = selectedDate.getDate();
		hour = selectedDate.getHours();
		minutes = selectedDate.getMinutes();

		if (timeFormat != militaryTime)
		{
			if (hour > 12)
			{
				hour -= 12;
				meridian = 'pm';
			}
			else if (hour == 12)
				meridian = 'pm';
			else
				meridian = 'am';
		}

		if (hour == 0 && meridian == 'am')
			hour = 12;

		displayDate = true;
	}

	drawCalendarStructure('datePicker');
	setupCalendar ('datePicker', year, month, day, hour, minutes, meridian, displayDate);
	displayCalendar('datePicker', 'datePickerBackground', e, false, taskId);
	scrollUpViewport(e);
};


function saveSelectAndHighlightDate(year, month, day, e)
{
	saveAndSelectDate(year, month, day, e);
	displaySelectedDate();
};

function saveDueDateBtnKeyDown(e)
{
	if (e.keyCode == 13)
	{
		saveTaskDueDate(taskId, taskId, document.getElementById('datePickerStringDisplay').value, false);
		//hideCalendar(calId, backgroundId, inModalWindow);
	}
	else
		e.preventDefault();
}


//clearDateAndTime function
//purpose: resets the date and time values in datePickerSelection
//parameters: none
function clearDateAndTime()
{
	//set values to 0
	datePickerData.year.value = 0;
	datePickerData.month.value = 0;
	datePickerData.day.value = 0;
	datePickerData.hour.value = 0;
	datePickerData.minutes.value = 0;
	datePickerData.meridian.value = '--';

	//unhighlight the selected date in UI, if it is present
	try
	{
		document.getElementById('selectedDate').removeAttribute('id');
	}
	catch(err){}

	displaySelectedDate();
};

//displayCalendar function
//purpose: unhides the date picker, sets up the click-to-dismiss background, positions the picker below the element that was triggered the function OR centers the picker in front of the modal window, sets up 'save' button for indiividual tasks or 'schedule' button for multi-edit actions
//parameters: calendarId, click-to-dismiss backgroundId, window event, boolean indicating if the picker needs to be displayed in front of a modal window, taskId of the task the picker will be updating upon clicking the 'save' button
function displayCalendar(calId, backgroundId, e, inModalWindow, taskId)
{
	var doc = document;

	var calendarEl = doc.getElementById(calId);
	var backgroundEl = doc.getElementById(backgroundId);
	var currentDisplay = calendarEl.style.display;

	if (currentDisplay == 'none')
	{
		//display calendar
		calendarEl.style.visibility = "hidden";  //this allows us to position the picker
		calendarEl.style.display = 'block';

		if (inModalWindow)
		{
			//IMPORTANT: anything done here to the modal window needs to be undone in the hideCalendar method
			//set up modal window
			var viewportHeight = doc.documentElement.clientHeight;
			var viewportWidth = doc.documentElement.clientWidth;
			var modalWindowEl = doc.getElementById('MODAL_WINDOW');
			var modalContentEl = doc.getElementById('MODAL_CONTENT');
			modalContentEl.style.height = "262px";
			modalContentEl.style.width = "336px";
			modalWindowEl.style.marginTop = "-" + (modalWindowEl.clientHeight/2) + "px";
			modalWindowEl.style.marginLeft = "-" + (modalWindowEl.clientWidth/2) + "px";

			//center calendar in front of modal view
			var xOrigin = viewportWidth/2 - calendarEl.clientWidth/2;
			var yOrigin = viewportHeight/2 - calendarEl.clientHeight/2 - 6; //-6 pixels highet of the center of the modal view

			calendarEl.style.position = "fixed";

			//hide save date button, which is not needed in multi-edit mode
			doc.getElementById('saveDueDateBtn').style.visibility = "hidden";
		}
		else
		{
			//position and display datePicker below the element clicked to trigger this function
			var xOrigin = e.target.offsetLeft;
			var yOrigin = e.target.offsetTop + e.target.clientHeight + 2;

			//set up background div for click-away to dismiss
			backgroundEl.onclick = function(){hideCalendar(calId, backgroundId);};
			backgroundEl.style.height = '100%';
			backgroundEl.style.width = '100%';
			backgroundEl.style.display = 'block';

			//set up saveDueDateBtn
			doc.getElementById('saveDueDateBtn').onclick = function(){
				saveTaskDueDate(taskId, taskId, doc.getElementById('datePickerStringDisplay').value, false);
				hideCalendar(calId, backgroundId, inModalWindow);
			};
		}

		calendarEl.style.top = yOrigin + 'px';
		calendarEl.style.left = xOrigin + 'px';
		calendarEl.style.visibility = "visible";
	}
	else
		hideCalendar(calId, backgroundId, inModalWindow);

};

//hideCalendar function
//purpose: hides the datePicker, dismisses its click-to-dismiss background, and dismisses the modal window if necessary
//parameters: a calendarId, its backgroundId, and a boolean indicating if the calendar was displayed in a modal window
function hideCalendar(calId, backgroundId, inModalWindow)
{
	var doc = document;

	var calendarEl = doc.getElementById(calId);
	var backgroundEl = doc.getElementById(backgroundId);

	//clear textfield and time picker
	doc.getElementById('datePickerStringDisplay').value = '';
	doc.getElementById('hourPicker').value = '00';
	doc.getElementById('minutesPicker').value = '00';
	doc.getElementById('meridianPicker').value = '--';

	//hide datepicker
	calendarEl.style.display = 'none';

	if(inModalWindow)
	{
		//set up modal window
		var modalWindowEl = doc.getElementById('MODAL_WINDOW');
		var modalContentEl = doc.getElementById('MODAL_CONTENT');
		modalContentEl.style.height = "auto";
		modalContentEl.style.width = "auto";
		modalWindowEl.style.marginTop = "-999px";
		modalWindowEl.style.marginLeft = "-999px";

		//center calendar in front of modal view
		calendarEl.style.position = "absolute";

		//hide save date button
		doc.getElementById('saveDueDateBtn').style.visibility = "visible";

	}else
	{
		//hide click-to-dismiss background
		backgroundEl.style.display = 'none';
		backgroundEl.style.height = '0px';
		backgroundEl.style.width = '0px;';
	}
};

//displayScheduleModal function
//purpose: displays the calendar picker 'inside' (in front) a modal window
//parameters: a window event
/*
function displayScheduleModal(e)
{
	displayModalWindow('multi_edit_schedule_due_date_modal');
	setupCalendar(calendarId, 0, 0, 0, 0, 0, null, false);
	displayCalendar('datePicker', 'datePickerBackground', e, true);
};
*/


//hideScheduleModal
//purpose: hides the date picker, the modal window and the multiEditActions menu
//parameters: the datepicker id
/*
function hideScheduleModal(calId)
{
	hideCalendar(calId, null, true);
	hideModalWindow();
	hideMultiEditActions();
};
*/


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



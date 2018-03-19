//console.log('Loading en_US.js');

var daysOfWeekStrings = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
var monthsStrings = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];


var taskStrings = [];
taskStrings.noDate = "No Date";			//describes that the task does not have set due date
taskStrings.unassigned = "Unassigned";	//describes that no one is assigned to the task
taskStrings.comment = "Comment";		//describes the ability to write a comment for a task
taskStrings.comments = "Comments";		//describes several comments as in '5 comments'
taskStrings.notes = "Notes";			//describes several notes
taskStrings.noContext = "Add a context";	//describes the abscence of a context
taskStrings.repeat = "Repeat";			//repeat
taskStrings.tag = "Tag";
taskStrings.tags = "Tags";
taskStrings.subtask = "Subtask";
taskStrings.subtasks = "Subtasks";
taskStrings.repeatWithParent = "Repeat with parent";
taskStrings.repeatWeekly = "Weekly";
taskStrings.repeatMonthly = "Monthly";
taskStrings.repeatYearly = "Yearly";
taskStrings.repeatDaily = "Daily";
taskStrings.repeatBiweekly = "Biweekly";
taskStrings.repeatBimonthly = "Bimonthly";
taskStrings.repeatSemiannually = "Semiannually";
taskStrings.repeatWithQuarterly = "Quarterly";
taskStrings.repeatNone = "None";
taskStrings.repeat = "Repeat";
taskStrings.noTags = "Add a tag";
taskStrings.normalType = "Normal";
taskStrings.projectType = "Project";
taskStrings.checklistType = "Checklist";
taskStrings.callType = "Call a Contact";
taskStrings.emailType = "Email a Contact";
taskStrings.SMSType = "SMS a Contact";
taskStrings.locationType = "Visit a Location";
taskStrings.websiteType = "Visit a Website";
taskStrings.unknownType = "Unknown";
taskStrings.enterPhoneNumber = "Enter a phone number...";
taskStrings.enterWebsiteAddress = "Enter a website address...";
taskStrings.enterEmailAddress = "Enter an email address...";
taskStrings.enterStreetAddress = "Enter a street address...";
taskStrings.priority = "Priority";
taskStrings.star = "Star";
taskStrings.dueDate = "Due Date";
taskStrings.list = "List";
taskStrings.taskAssignment = "Task Assignment";
taskStrings.deleteTask = "Delete Task";
taskStrings.context = "Context";
taskStrings.repeatInterval = "Repeat Interval";
taskStrings.taskType = "Task Type";


var pickerStrings = [];
pickerStrings.normalTaskDescription = "A normal task has a due date, due time, priority, etc.";
pickerStrings.projectDescription = "A project has all the properties of a normal task and it can have normal tasks and checklist under it";
pickerStrings.checklistDescription = "A checklist has all the properties of a normal task and it can hold a list of taskitos";
pickerStrings.phoneNumber = "Phone Number";
pickerStrings.emailAddress = "Email Address";
pickerStrings.address = "Address";
pickerStrings.url = "Website Address";


var controlStrings = [];
controlStrings.all = "All Tasks";
controlStrings.noTags = "No Tags";
controlStrings.createTag = "Create Tag";
controlStrings.noContext = "No Context";
controlStrings.context = "Context";
controlStrings.contexts = "Contexts";
controlStrings.createContext = "Create a Context";
controlStrings.lists = "Lists";
controlStrings.createList = "Create a List";
controlStrings.today = "Today";
controlStrings.focus = "Focus List";
controlStrings.starred = "Starred Tasks";
controlStrings.inbox = "Inbox";
controlStrings.unassigned = "Unassigned";
controlStrings.everyone = "All";
controlStrings.me = "Me";
controlStrings.myTasks = "My Tasks";
controlStrings.everyonesTasks = "Everyone\\'s Tasks";
controlStrings.unassignedTasks = "Unassigned";
controlStrings.someonesTasks = "\\'s Tasks";

var alertStrings = [];
alertStrings.none = 'None';
alertStrings.zeroMinutesBefore = '0 minutes before';
alertStrings.fiveMinutesBefore = '5 minutes before';
alertStrings.fifteenMinutesBefore = '15 minutes before';
alertStrings.thirtyMinutesBefore = '30 minutes before';
alertStrings.oneHourBefore = '1 hour before';
alertStrings.twoHoursBefore = '2 hours before';
alertStrings.oneDayBefore = '1 day before';
alertStrings.twoDaysBefore = '2 days before';
alertStrings.other = 'Other';
alertStrings.alertDelivery  = 'Task Alert';
alertStrings.taskAlerts = 'Task Alerts';
alertStrings.alertSound = 'Alert sound';
alertStrings.bells = 'Bells';
alertStrings.flute = 'Flute';
alertStrings.morse = 'Morse';
alertStrings.data = 'Data';
alertStrings.currentAlerts = 'Current Alerts';
alertStrings.minutes = 'Minutes';
alertStrings.hours = 'Hours';
alertStrings.days = 'Days';
alertStrings.address = 'Address';
alertStrings.type = 'Location Alert Type';
alertStrings.whenILeave = 'When I Leave';
alertStrings.whenIArrive = 'When I Arrive';
alertStrings.scheduleAlert = 'Schedule Alert';
alertStrings.locationAlert = 'Location Alert';
alertStrings.unknownTask = 'Unknown Task';
alertStrings.noDueDate = 'No Due Date';

var settingStrings = [];
	settingStrings.general = 'General';
	settingStrings.notifications = 'Notifications';
//    settingStrings.messageCenter = 'Announcements';
	settingStrings.account = 'Account';
	settingStrings.accountDetails = 'Details';
	settingStrings.premiunAccount = 'Premium Account';
	settingStrings.invitations = 'Invitations';
	settingStrings.focusList = 'Focus List';
    settingStrings.taskCreation = 'Task Creation';
	settingStrings.referrals = 'Referrals';
	settingStrings.teaming = 'Todo for Business';
	settingStrings.teaming_create = 'Create Your Team';
	settingStrings.teaming_overview = 'Overview';
	settingStrings.teaming_members = 'Team Members';
	settingStrings.integrations = 'Integrations';
	settingStrings.teaming_lists = 'Shared Lists';
	settingStrings.teaming_billing = 'Licenses & Billing';
	settingStrings.support = 'Support';
	settingStrings.email_support = 'Contact support';

	/*Subscriptions*/
	settingStrings.accountExpired = 'Your premium account expired on';
	settingStrings.accountWillExpire = 'Your premium account will expire on';
	settingStrings.downgradeAccount = 'Downgrade Account';
	settingStrings.renew = 'Renew / Update';
	settingStrings.upgrade = 'Upgrade';

	/*Generic*/
	settingStrings.updated = 'Updated';



var taskSectionsStrings = [];
taskSectionsStrings.today = "Today";
taskSectionsStrings.new_ = "New";
taskSectionsStrings.overdue = "Overdue";
taskSectionsStrings.tomorrow = "Tomorrow";
taskSectionsStrings.nextsevendays = "Next Seven Days";
taskSectionsStrings.future = "Future";
taskSectionsStrings.noduedate = "No Due Date";
taskSectionsStrings.completed = "Completed";
taskSectionsStrings.search = "Search Results";


taskSectionsStrings.high = "High";
taskSectionsStrings.medium = "Medium";
taskSectionsStrings.low = "Low";
taskSectionsStrings.none = "None";
taskSectionsStrings.incomplete = "Incomplete";

//! subscriptionStrings

var subStrings = [];
subStrings.subscriptionFor = "Subscription for";
subStrings.expired = "Expired";
subStrings.unknown = "Unknown";
subStrings.trial = "Trial";
subStrings.promo = "Promo";
subStrings.paid = "Paid";
subStrings.pro = "Pro";
subStrings.type = "Type";
subStrings.expires = "Expires";
subStrings.unused = "Unassigned";
subStrings.unassign = "Unassign";
subStrings.assign = "Assign";
subStrings.invitationSent = "sent";
subStrings.deleteInvitation = "Delete Invitation";
subStrings.resendInvitation = "Resend Invitation";
subStrings.owner = "Owner";
subStrings.cancel = "Cancel";
subStrings.addSubscription = "Add Subscription";
subStrings.notEligible = "Not eligible";




var monthStrings = [];

monthStrings.january = "January";
monthStrings.february = "February";
monthStrings.march = "March";
monthStrings.april = "April";
monthStrings.may = "May";
monthStrings.june = "June";
monthStrings.july ="July";
monthStrings.august = "August";
monthStrings.september = "September";
monthStrings.october = "October";
monthStrings.november = "November";
monthStrings.december = "December";


var migrationStrings = [];

migrationStrings.migrationStarted = "Your account is being upgraded.";
//migrationStrings.migrationStarted = [	'Your account is being upgraded with awesomeness.',
//										'Your account is being upgraded and filled with awesome features.',
//										'Hold on one sec while we make your account even more awesome than it was.',
//										'Grab your popcorn, your account is being upgraded to a first class version of Todo Cloud.',
//										'Hold onto your socks because they might get knocked off with this upgrade.',
//										'Did you know your account is about to be upgraded to the a super cool new Todo Cloud?',
//										'Wait for it...Wait for it...You\'re upgrading!',
//										'You\'re about to enter the realm of even greater possibilities. Get ready for your account to be upgraded.'
//									];
migrationStrings.migrationTimedOut = "Todo Cloud is still busy upgrading your account. Please refresh this page again later.";
//migrationStrings.migrationTimedOut = [	'Shucks...you must be busy, our system got stressed from all your tasks! Try again in a minute or two.',
//										'Whoops! Our system got a little overworked. Give it a minute and try again. We promise it\'s worth the wait.',
//										'Oh, it looks like there is a rain delay. Try back again in a few minutes and let our servers dry off.',
//										'It\'s not you, it\'s me. Really, this just taking a bit longer than normal. Give it a try again in a few minutes.',
//										'We forgot the flux capacitor! We need to call Doc real quick. Give it another go in a few minutes.',
//										'Wow! How many tasks did you have in there? Try again in a few minutes.',
//										'Our crystals seem to be low on power. Let them recharge for a second and try again.',
//										'This might take us a little more time. Go grab yourself a cold beverage and try again.'
//									];
migrationStrings.migrationSuccessful = "Welcome to Todo Cloud. Enjoy!";
//migrationStrings.migrationSuccessful = ['Your account has been slathered in awesome sauce and is now ready for you!',
//										'I hope that you\'re ready to take this relationship to the next level because...your account is ready!',
//										'Get ready to say WOW! Your new fully upgraded account is ready to go.',
//										'Buckle up because we\'re going full throttle into your newly upgraded account!',
//										'Victory! You just won the gold medal, your account is ready!',
//										'Ding! All Done! We just microwaved your account into a delicious new treat. Enjoy.',
//										'Welcome to the VIP section, we\'ve got your upgrade waiting for you.'
//									];

var dateStrings = {};
dateStrings.months = ["Whoops!", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
dateStrings.days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
//dateStrings.days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];


var maintenanceStrings = [];
maintenanceStrings.maintenanceTimedOut = "Todo Cloud is still busy performing maintenance on your account. Please try again later.";
maintenanceStrings.maintenanceStarted = "Todo Cloud is performing maintenance on your account. Thank you for your patience.";
maintenanceStrings.maintenanceSuccessful = "Todo Cloud is finished performing maintenance on your account.";

//console.log('Finished loading en_US.js');

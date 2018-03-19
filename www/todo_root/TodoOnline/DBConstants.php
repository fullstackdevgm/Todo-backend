<?php

include_once('TodoOnline/DBCredentials.php');

// Administrator Levels
define('ADMIN_LEVEL_NONE', 0);
define('ADMIN_LEVEL_SUPPORT', 10);
define('ADMIN_LEVEL_DEVELOPER', 20);
define('ADMIN_LEVEL_ROOT', 47);
define('ADMIN_LEVEL_SUPER_ROOT', 100);
	
// used in tdo_board_memberships
define('LIST_MEMBERSHIP_VIEWER', 0);
define('LIST_MEMBERSHIP_MEMBER', 1);
define('LIST_MEMBERSHIP_OWNER', 2);
	
// Changelog defines
define('CHANGE_TYPE_ADD', 1);
define('CHANGE_TYPE_MODIFY', 2);
define('CHANGE_TYPE_DELETE', 3);	
define('CHANGE_TYPE_RESTORE', 4);	

define('ITEM_TYPE_LIST', 1);
define('ITEM_TYPE_USER', 2);
define('ITEM_TYPE_EVENT', 3);	
define('ITEM_TYPE_COMMENT', 4);	
define('ITEM_TYPE_NOTE', 5);
define('ITEM_TYPE_INVITATION', 6);
define('ITEM_TYPE_TASK', 7);
define('ITEM_TYPE_CONTEXT', 8);
define('ITEM_TYPE_NOTIFICATION', 9);
define('ITEM_TYPE_TASKITO', 10);
define('ITEM_TYPE_TEAM_INVITATION', 11);

// Changelog defines
define('CHANGE_LOCATION_WEB', 1);
define('CHANGE_LOCATION_CALDAV', 2);
define('CHANGE_LOCATION_SYNC', 3);
define('CHANGE_LOCATION_MIGRATION', 4);
define('CHANGE_LOCATION_EMAIL', 5);
    
    
// User Notification defines
define('USER_NOTIFICATION_TYPE_INVITATION', 1);
define('USER_NOTIFICATION_TYPE_COMMENT', 2);
define('USER_NOTIFICATION_TYPE_CHANGELOG', 3);	    
define('USER_NOTIFICATION_TYPE_SYSTEM', 4);	    

	
// Task defines
define ('TASK_TABLE_FIELDS', 'taskid, listid, name, parentid, note, startdate, duedate, due_date_has_time, completiondate, priority, timestamp, caldavuri, caldavdata, deleted, task_type, type_data, starred, assigned_userid, recurrence_type, advanced_recurrence_string, project_startdate, project_duedate, project_duedate_has_time, project_priority, project_starred, location_alert, sort_order');
//These are the fields that stay the same when moving tasks between tables (everything except for deleted, timestamp, completiondate)
define ('TASK_TABLE_FIELDS_STATIC', 'taskid, listid, name, parentid, note, startdate, duedate, due_date_has_time, priority, caldavuri, caldavdata, task_type, type_data, starred, assigned_userid, recurrence_type, advanced_recurrence_string, project_startdate, project_duedate, project_duedate_has_time, project_priority, project_starred, location_alert, sort_order');
    
// Task Creation Email System (newtask.todo-cloud.com)
define('EMAIL_TASK_CREATION_USERID', '47BC13B3-4C76-4F60-BD19-F89D27A86547');
	
// User Subscription Levels
// When a user's subscription is first configured (or updated), the type of
// purchase/upgrade will be recorded on their subscription record.  The
// SUBSCRIPTION_LEVEL_EXPIRED will never be recorded in the DB, but only
// returned by TDOSession::getSubscriptionLevel() if it's determined that the
// user's expiration date is bad.
define('SUBSCRIPTION_LEVEL_EXPIRED', 0);
define('SUBSCRIPTION_LEVEL_UNKNOWN', 1);
define('SUBSCRIPTION_LEVEL_TRIAL', 2);
define('SUBSCRIPTION_LEVEL_PROMO', 3);
define('SUBSCRIPTION_LEVEL_PAID', 4);
define('SUBSCRIPTION_LEVEL_MIGRATED', 5);
define('SUBSCRIPTION_LEVEL_PRO', 6);
define('SUBSCRIPTION_LEVEL_GIFT', 7);
define('SUBSCRIPTION_LEVEL_TEAM', 8);

// 14 days (in seconds)
define('SUBSCRIPTION_DEFAULT_TRIAL_DAYS', 1209600);
define('SUBSCRIPTION_MIGRATION_BONUS', 1209600);
	
// Payment System Types used in the tdo_user_payment_system table.  This table
// tracks a Todo Cloud user's last successful subscription purchase.
define('PAYMENT_SYSTEM_TYPE_UNKNOWN', 0);
define('PAYMENT_SYSTEM_TYPE_STRIPE', 1);
define('PAYMENT_SYSTEM_TYPE_IAP', 2);
define('PAYMENT_SYSTEM_TYPE_PAYPAL', 3);
define ('PAYMENT_SYSTEM_TYPE_IAP_AUTORENEW', 4);
define('PAYMENT_SYSTEM_TYPE_GOOGLE_PLAY_AUTORENEW', 5);
define('PAYMENT_SYSTEM_TYPE_TEAM', 6); // Used to communicate with clients about subscription
define('PAYMENT_SYSTEM_TYPE_WHITELISTED', 7); // Used to communicate with clients about subscription

// Google play constants
define ('SYSTEM_SETTING_GOOGLE_PLAY_ACCESS_TOKEN', 'GOOGLE_PLAY_ACCESS_TOKEN');
define ('SYSTEM_SETTING_GOOGLE_PLAY_REFRESH_TOKEN', 'GOOGLE_PLAY_REFRESH_TOKEN');
define ('SYSTEM_SETTING_GOOGLE_PLAY_CLIENT_ID', 'GOOGLE_PLAY_CLIENT_ID');
define ('SYSTEM_SETTING_GOOGLE_PLAY_CLIENT_SECRET', 'GOOGLE_PLAY_CLIENT_SECRET');

	
// these are defined to be used for storing the settings
// of these lists.  The ID is the same for everyone but
// it's also tied to their USERID which makes it unique
define('ALL_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829F829ALL');
define('FOCUS_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829E3FOCUS');
define('STARRED_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829STARRED');
define('UNFILED_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829UNFILED');
	
define('TEAM_SUBSCRIPTION_STATE_EXPIRED', 0);
define('TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD', 1);
define('TEAM_SUBSCRIPTION_STATE_ACTIVE', 2);
define('TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD', 3);

// Stripe details moved to config.php (DTW - 8/4/14)        
	
	
	// Reliving the Caldera days for Calvin...
	
/*
 
 ..... ___@
 ... _`\<,_
 .. (*)/ (*)
 .. ~~~~~~~~
 
         _____
		q o o p
		q o!o p
		d o!o b
         \!!!/
         |===|
         |!!!|
         |!!!|
         |!!!|
         |!!!|
         |!!!|
        _|!!!|__
      .+=|!!!|--.`.
    .'   |!!!|   `.\
   /     !===!     \\
   |    /|!!!|\    ||
    \   \!!!!!/   //
     )   `==='   ((
   .'    !!!!!    `..
  /      !!!!!      \\
 |       !!!!!       ||
 |       !!!!!       ||
 |       !!!!!       ||
 \      =======     //
  `.               /
	`-.________.-'
 
 */
	
	
?>

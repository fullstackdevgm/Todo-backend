<?php
	// TODO get this from our git commit?
	include_once('TodoOnline/version.php');

    $staticVersion = '1.0.0';
    if (defined('TDO_VERSION')) {
        $staticVersion = TDO_VERSION;
    }
    define('BASE_DIR', dirname(__FILE__));
	//
	// CSS Files
	//
	
	define('TP_CSS_PATH_BASE', SITE_BASE_S3_URL . 					'css/base.css?v=' . $staticVersion);
	
	define('TP_CSS_PATH_STYLE', SITE_BASE_S3_URL . 					'css/style.css?v=' . $staticVersion);
	//define('TP_CSS_PATH_STYLE', '/debug_local/style.css?v=' . $staticVersion);
	
	define('TP_CSS_PATH_APP_SETTINGS', SITE_BASE_S3_URL . 			'css/appSettings.css?v=' . $staticVersion);
	define('TP_CSS_PATH_LIST_SETTINGS', SITE_BASE_S3_URL . 			'css/listSettings.css?v=' . $staticVersion);
	
	define('TP_CSS_PATH_PRINT_STYLE', SITE_BASE_S3_URL . 			'css/printStyle.css?v=' . $staticVersion);
	
	define('TP_CSS_PATH_ADMIN_STYLE', SITE_BASE_S3_URL . 			'css/adminStyle.css?v=' . $staticVersion);
	
	//
	// Javascript Files
	//
	
	define('TP_JS_PATH_APP_SETTINGS_FUNCTIONS', SITE_BASE_S3_URL . 			'js/AppSettingsFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_BROWSER_NOTIFICATION_FUNCTIONS', SITE_BASE_S3_URL . 	'js/BrowserNotificationFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_CHANGELOG_FUNCTIONS', SITE_BASE_S3_URL . 			'js/ChangelogFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_COMMENT_FUNCTIONS', SITE_BASE_S3_URL . 				'js/CommentFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_COMPLETED_TASK_FUNCTIONS', SITE_BASE_S3_URL . 		'js/CompletedTaskFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_CONTEXT_FUNCTIONS', SITE_BASE_S3_URL . 				'js/ContextFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_DASHBOARD_CONTROLS', SITE_BASE_S3_URL . 				'js/DashboardControls.js?v=' . $staticVersion);
	define('TP_JS_PATH_INVITATION_FUNCTIONS', SITE_BASE_S3_URL . 			'js/InvitationFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_LANDING_PAGE_FUNCTIONS', SITE_BASE_S3_URL . 			'js/LandingPageFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_LIST_FUNCTIONS', SITE_BASE_S3_URL . 					'js/ListFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_LIST_DELETE_FUNCTIONS', SITE_BASE_S3_URL . 			'js/ListDeleteFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_LIST_MEMBER_FUNCTIONS', SITE_BASE_S3_URL . 			'js/ListMemberFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_MULTI_EDIT_FUNCTIONS', SITE_BASE_S3_URL . 			'js/MultiEditFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_RESET_PASSWORD_FUNCTIONS', SITE_BASE_S3_URL . 		'js/ResetPasswordFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TAG_FUNCTIONS', SITE_BASE_S3_URL . 					'js/TagFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TASK_FUNCTIONS', SITE_BASE_S3_URL . 					'js/TaskFunctions.js?v=' . $staticVersion);
	//define('TP_JS_PATH_TASK_FUNCTIONS', '/debug_local/TaskFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TASK_TAG_FUNCTIONS', SITE_BASE_S3_URL . 				'js/TaskTagFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TASK_FILTER_FUNCTIONS', SITE_BASE_S3_URL . 			'js/TaskFilterFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TEAM_FUNCTIONS', SITE_BASE_S3_URL .					'js/TeamFunctions.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_UTIL_FUNCTIONS', SITE_BASE_S3_URL . 					'js/TDOUtilFunctions.js?v=' . $staticVersion);
	//define('TP_JS_PATH_UTIL_FUNCTIONS', '/debug_local/TDOUtilFunctions.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_VERIFY_EMAIL_FUNCTIONS', SITE_BASE_S3_URL . 			'js/VerifyEmailFunctions.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_TASK_EDITOR_FUNCTIONS', SITE_BASE_S3_URL . 			'js/TaskEditorFunctions.js?v=' . $staticVersion);
    define('TP_JS_PATH_TASK_REPEAT_FUNCTIONS', SITE_BASE_S3_URL .           'js/TaskRepeatFunctions.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_DATE_PICKER', SITE_BASE_S3_URL . 					'js/datePicker.js?v=' . $staticVersion);
	define('TP_JS_PATH_NEW_DATE_PICKER', SITE_BASE_S3_URL . 				'js/NewDatePicker.js?v=' . $staticVersion);
	//define('TP_JS_PATH_NEW_DATE_PICKER', '/debug_local/NewDatePicker.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_DRAG_N_DROP_FUNCTIONS', SITE_BASE_S3_URL .			'js/DragNDropFunctions.js?v=' . $staticVersion);
	
	define('TP_JS_PATH_ADMIN_UTILS', SITE_BASE_S3_URL . 					'js/AdminUtils.js?v=' . $staticVersion);
	define('TP_JS_PATH_PROMO_CODE_FUNCTIONS', SITE_BASE_S3_URL . 			'js/promoCodeFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_SYS_INFO_FUNCTIONS', SITE_BASE_S3_URL . 				'js/systemInfoFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_USER_FUNCTIONS', SITE_BASE_S3_URL . 					'js/userFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_TEAM_ADMIN_FUNCTIONS', SITE_BASE_S3_URL . 			'js/teamAdminFunctions.js?v=' . $staticVersion);
    define('TP_JS_PATH_GIFT_CODE_FUNCTIONS', SITE_BASE_S3_URL .             'js/giftCodeFunctions.js?v=' . $staticVersion);
    
    define('TP_JS_PATH__SYSTEM_NOTIFICATION_FUNCTIONS', SITE_BASE_S3_URL .  'js/AdminSystemNotificationFunctions.js?v=' . $staticVersion);
//    define('TP_JS_PATH_ADMIN_MESSAGE_CENTER_FUNCTIONS', SITE_BASE_S3_URL .  'js/AdminMessageCenterFunctions.js?v=' . $staticVersion);
	
    define('TP_JS_PATH_APPLY_GIFT_CODE_FUNCTIONS', SITE_BASE_S3_URL .       'js/ApplyGiftCodeFunctions.js?v=' . $staticVersion);
	define('TP_JS_PATH_APPLY_REFERRAL_LINK_FUNCTIONS', SITE_BASE_S3_URL .	'js/ApplyReferralLinkFunctions.js?v=' . $staticVersion);
    define('TP_JS_PATH_ADMIN_REFERRAL_FUNCTIONS', SITE_BASE_S3_URL .       'js/AdminReferralFunctions.js?v=' . $staticVersion);
    
    define('TP_JS_PATH_NOTIFICATION_SETTINGS_FUNCTIONS', SITE_BASE_S3_URL . 'js/NotificationSettingsFunctions.js?v=' . $staticVersion);
//    define('TP_JS_PATH_MESSAGE_CENTER_FUNCTIONS', SITE_BASE_S3_URL .       'js/MessageCenterFunctions.js?v=' . $staticVersion);
    
	//
	// Language Files
	//
	
	// TODO: Eventually, this may be the place where we can evaluate what
	// language should be included.
	define('TP_JS_PATH_LANG', SITE_BASE_S3_URL . 'js/languages/lang.php?v=' . $staticVersion);
	
	//
	// Image Files
	//
	
	define('TP_IMG_PATH_FAV_ICON', SITE_BASE_S3_URL . 	'images/favicon.png?v=' . $staticVersion);
	define('TP_IMG_GIFT_CODE_SHOWCASE', SITE_BASE_S3_URL . 'images/Todo-Cloud-Productivity-Gift.jpg?v=' . $staticVersion);
	define('TP_IMG_GIFT_CODE_SHOWCASE_2X', SITE_BASE_S3_URL . 'images/Todo-Cloud-Productivity-Gift@2x.jpg?v=' . $staticVersion);
	define('TP_IMG_GIFT_CODE_SHOWCASE_ZH_CN', SITE_BASE_S3_URL . 'images/zh-cn-Todo-Cloud-Productivity-Gift.jpg?v=' . $staticVersion);
	define('TP_IMG_GIFT_CODE_SHOWCASE_2X_ZH_CN', SITE_BASE_S3_URL . 'images/zh-cnTodo-Cloud-Productivity-Gift@2x.jpg?v=' . $staticVersion);
	define('TP_IMG_FB_SHARE_BUTTON', SITE_BASE_S3_URL . 'images/facebook-share-button.png?v=' . $staticVersion);
	define('TP_IMG_FB_SHARE_BUTTON_2X', SITE_BASE_S3_URL . 'images/facebook-share-button.png@2x?v=' . $staticVersion);
	define('TP_IMG_REFERRAL_SHOWCASE', SITE_BASE_S3_URL . 'images/todo-pro-referral-showcase.jpg?v=' . $staticVersion);
	define('TP_IMG_REFERRAL_SHOWCASE_2X', SITE_BASE_S3_URL . 'images/todo-pro-referral-showcase@2x.jpg?v=' . $staticVersion);
	
	// The following files really need to be rolled into the sprite, but until
	// we do that ...
	
	define('TP_IMG_PATH_CC_LOGOS', SITE_BASE_S3_URL . 		'images/cclogos.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CC_QUESTION', SITE_BASE_S3_URL .	'images/question_mark.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CC_LOCK', SITE_BASE_S3_URL . 		'images/lock.png?v=' . $staticVersion);
	define('TP_IMG_PATH_TASK_ASSIGNED', SITE_BASE_S3_URL .	'images/assigned_on_icon.png?v=' . $staticVersion);
	define('TP_IMG_PATH_LIST_EDIT', SITE_BASE_S3_URL .		'images/list_edit.png?v=' . $staticVersion);
	
	define('TP_IMG_PATH_VIEW_BACKGROUND', SITE_BASE_S3_URL_EMAIL . 'images/view-background.png?v=' . $staticVersion);
	define('TP_IMG_PATH_VIEW_BACKGROUND2X', SITE_BASE_S3_URL_EMAIL . 'images/view-background@2x.png?v=' . $staticVersion);
	define('TP_IMG_PATH_TP_PRO_EMAIL_LOGO', SITE_BASE_S3_URL_EMAIL . 'images/Todo-Cloud-Logo-29h.png?v=' . $staticVersion);
	define('TP_IMG_PATH_TP_PRO_EMAIL_LOGO2X', SITE_BASE_S3_URL_EMAIL . 'images/Todo-Cloud-Logo-58h.png?v=' . $staticVersion);
	
	define('TP_IMG_PATH_CHANGELOG_LIST', SITE_BASE_S3_URL . 	'images/changelog-type-list.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CHANGELOG_PERSON', SITE_BASE_S3_URL .	'images/changelog-type-person.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CHANGELOG_EVENT', SITE_BASE_S3_URL . 	'images/changelog-type-event.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CHANGELOG_COMMENT', SITE_BASE_S3_URL . 	'images/changelog-type-comment.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CHANGELOG_INVITE', SITE_BASE_S3_URL . 	'images/changelog-type-invite.png?v=' . $staticVersion);
	define('TP_IMG_PATH_CHANGELOG_TASK', SITE_BASE_S3_URL . 	'images/changelog-type-task.png?v=' . $staticVersion);
	
	
	//
	// System Settings Defaults
	//
	define('DEFAULT_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', 'P30D');
	
	// Todo for Business
	//
	// Specify when a reminder email should be sent to Todo for Business team
	// members that are identified as being an IAP customer. As of Todo Cloud Web
	// 2.4, IAP customers can join Todo for Business teams, but in order for them
	// to get the full benefits of a team (subscription paid for by team), they have
	// to cancel their auto-renewing IAP subscription. The system will send them
	// a reminder email with instructions.
	define('DEFAULT_SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL', 'P7D');
	define('DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER', 4.00);
	define('DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER', 40.00);
	
	define('DEFAULT_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL', 'P7D');
	
	// When a brand new team is created, we ask the creator how they found us.
	// We allow this to be changed on-the-fly by a system setting, but here are
	// the default items.
    //i18n
    _('App Store');
    _('Appigo.com');
    _('Web search');
    _('Article');
    _('Social media');
    _('I\'m a Todo user');
    _('Referred by a friend');
    _('Other');
	define('DEFAULT_SYSTEM_SETTING_DISCOVERY_ANSWERS', "App Store, Appigo.com, Web search, Article, Social media, I'm a Todo user, Referred by a friend, Other");
	
	// Teams that were created before this date get the original team pricing
	define('DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_DATE', '2015-11-30T23:59:59+00:00');
	define('DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE', 1.99);
	define('DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE', 19.99);
	
	// Onboarding Email Daemon Statics
	define('DEFAULT_SYSTEM_SETTING_ONBOARDING_DAEMON_SLEEP_INTERVAL', 'PT1H');
	// This value is used to create an MD5 hashed key for the unsubscribe link
	// sent with onboarding emails
	define ("OPT_OUT_EMAIL_HASH", '86104B2D-DC10-4538-9A0E-61E974565D5E');

	//Enable Slack integration for internal use only
    define ("SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY", TRUE);
    define ("SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID", '7408d4aa-8322-dc89-72be-000018be2610');


	// The settings from the defaults below should ONLY be changed on a live
	// system in TESTING MODE ONLY.
	define('DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS', 86400); // One day
	define('DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS', 3600); // One hour
	define('DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', 'P1M'); // One month
	define('DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', 'P1Y'); // One year
	define('DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', 'P14D'); // 14 days

    define('DEFAULT_LOCALE', 'en_US');
    define('DEFAULT_LOCALE_ENCODING', 'UTF-8');
    define('DEFAULT_LOCALE_TEXTDOMAIN', 'todo_locale');
    
// don't use ? > php end to avoid emitting unwanted spaces

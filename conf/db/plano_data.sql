# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.6.17)
# Database: todoonline_db
# Generation Time: 2014-07-22 20:45:24 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table appigo_email_list_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `appigo_email_list_user`;

CREATE TABLE `appigo_email_list_user` (
  `email` varchar(100) NOT NULL,
  `last_source` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `appigo_email_idx` (`email`(15))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table appigo_email_verifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `appigo_email_verifications`;

CREATE TABLE `appigo_email_verifications` (
  `verificationid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_email_verifications_pk` (`verificationid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table appigo_password_reset
# ------------------------------------------------------------

DROP TABLE IF EXISTS `appigo_password_reset`;

CREATE TABLE `appigo_password_reset` (
  `resetid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_password_reset_pk` (`resetid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table appigo_user_account_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `appigo_user_account_log`;

CREATE TABLE `appigo_user_account_log` (
  `userid` varchar(36) NOT NULL,
  `owner_userid` varchar(36) NOT NULL,
  `change_type` tinyint(4) NOT NULL DEFAULT '0',
  `description` varchar(512) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_user_account_log_userid` (`userid`(10)),
  KEY `tdo_user_account_log_owner_userid` (`owner_userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table appigo_user_accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `appigo_user_accounts`;

CREATE TABLE `appigo_user_accounts` (
  `userid` varchar(36) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT NULL,
  `email_opt_out` tinyint(1) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `oauth_provider` tinyint(4) NOT NULL DEFAULT '0',
  `oauth_uid` varchar(36) DEFAULT NULL,
  `first_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `admin_level` tinyint(1) NOT NULL DEFAULT '0',
  `deactivated` tinyint(1) DEFAULT NULL,
  `last_reset_timestamp` int(11) DEFAULT NULL,
  `creation_timestamp` int(11) NOT NULL DEFAULT '0',
  `image_guid` varchar(36) DEFAULT NULL,
  `image_update_timestamp` int(11) DEFAULT NULL,
  KEY `tdo_user_accounts_username_index` (`username`(10)),
  KEY `tdo_user_accounts_creation_timestamp` (`creation_timestamp`),
  KEY `tdo_user_accounts_pk` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `appigo_user_accounts` WRITE;
/*!40000 ALTER TABLE `appigo_user_accounts` DISABLE KEYS */;

INSERT INTO `appigo_user_accounts` (`userid`, `username`, `email_verified`, `email_opt_out`, `password`, `oauth_provider`, `oauth_uid`, `first_name`, `last_name`, `admin_level`, `deactivated`, `last_reset_timestamp`, `creation_timestamp`, `image_guid`, `image_update_timestamp`)
VALUES
	('02eb7a43-a8fe-b069-c261-00004355b951','vinnie.tenk@gmail.com',1,0,'767828d004286280f14f4649af14d53f',0,NULL,'Vinnie','Tenk',0,NULL,0,1380652245,NULL,0);

/*!40000 ALTER TABLE `appigo_user_accounts` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tdo_archived_taskitos
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_archived_taskitos`;

CREATE TABLE `tdo_archived_taskitos` (
  `taskitoid` varchar(36) NOT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_archived_taskitos_pk` (`taskitoid`(10)),
  KEY `tdo_archived_taskitos_parentid_index` (`parentid`(10)),
  KEY `tdo_archived_taskitos_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_archived_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_archived_tasks`;

CREATE TABLE `tdo_archived_tasks` (
  `taskid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `note` text,
  `startdate` int(11) NOT NULL DEFAULT '0',
  `duedate` int(11) NOT NULL DEFAULT '0',
  `due_date_has_time` tinyint(1) NOT NULL DEFAULT '0',
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `caldavuri` varchar(255) DEFAULT NULL,
  `caldavdata` blob,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `task_type` int(11) NOT NULL DEFAULT '0',
  `type_data` text,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_userid` varchar(36) DEFAULT NULL,
  `recurrence_type` int(11) NOT NULL DEFAULT '0',
  `advanced_recurrence_string` varchar(255) DEFAULT NULL,
  `project_startdate` int(11) DEFAULT NULL,
  `project_duedate` int(11) DEFAULT NULL,
  `project_duedate_has_time` tinyint(1) DEFAULT NULL,
  `project_priority` int(11) DEFAULT NULL,
  `project_starred` tinyint(1) DEFAULT NULL,
  `location_alert` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_archived_tasks_pk` (`taskid`(10)),
  KEY `tdo_archived_tasks_listid_index` (`listid`(10)),
  KEY `tdo_archived_tasks_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_autorenew_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_autorenew_history`;

CREATE TABLE `tdo_autorenew_history` (
  `subscriptionid` varchar(36) NOT NULL,
  `renewal_attempts` tinyint(4) NOT NULL DEFAULT '0',
  `attempted_time` int(11) NOT NULL DEFAULT '0',
  `failure_reason` varchar(255) DEFAULT NULL,
  KEY `tdo_autorenew_history_pk` (`subscriptionid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_bounced_emails
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_bounced_emails`;

CREATE TABLE `tdo_bounced_emails` (
  `email` varchar(100) NOT NULL,
  `bounce_type` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `bounce_count` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_bounced_emails_email` (`email`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_change_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_change_log`;

CREATE TABLE `tdo_change_log` (
  `changeid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `itemid` varchar(36) DEFAULT NULL,
  `item_name` varchar(72) DEFAULT NULL,
  `item_type` smallint(6) NOT NULL DEFAULT '0',
  `change_type` smallint(6) NOT NULL DEFAULT '0',
  `targetid` varchar(36) DEFAULT NULL,
  `target_type` smallint(6) NOT NULL DEFAULT '0',
  `mod_date` int(11) NOT NULL DEFAULT '0',
  `serializeid` varchar(36) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `change_location` tinyint(1) NOT NULL DEFAULT '0',
  `change_data` text,
  KEY `tdo_change_log_pk` (`changeid`(10)),
  KEY `tdo_change_log_listid_index` (`listid`(10)),
  KEY `tdo_change_log_itemid_index` (`itemid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_comments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_comments`;

CREATE TABLE `tdo_comments` (
  `commentid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `itemid` varchar(36) DEFAULT NULL,
  `item_type` int(11) NOT NULL DEFAULT '0',
  `item_name` varchar(72) DEFAULT NULL,
  `text` text,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  KEY `tdo_comments_pk` (`commentid`(10)),
  KEY `tdo_comments_itemid_index` (`itemid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_completed_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_completed_tasks`;

CREATE TABLE `tdo_completed_tasks` (
  `taskid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `note` text,
  `startdate` int(11) NOT NULL DEFAULT '0',
  `duedate` int(11) NOT NULL DEFAULT '0',
  `due_date_has_time` tinyint(1) NOT NULL DEFAULT '0',
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `caldavuri` varchar(255) DEFAULT NULL,
  `caldavdata` blob,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `task_type` int(11) NOT NULL DEFAULT '0',
  `type_data` text,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_userid` varchar(36) DEFAULT NULL,
  `recurrence_type` int(11) NOT NULL DEFAULT '0',
  `advanced_recurrence_string` varchar(255) DEFAULT NULL,
  `project_startdate` int(11) DEFAULT NULL,
  `project_duedate` int(11) DEFAULT NULL,
  `project_duedate_has_time` tinyint(1) DEFAULT NULL,
  `project_priority` int(11) DEFAULT NULL,
  `project_starred` tinyint(1) DEFAULT NULL,
  `location_alert` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_completed_tasks_pk` (`taskid`(10)),
  KEY `tdo_completed_tasks_listid_index` (`listid`(10)),
  KEY `tdo_completed_tasks_parentid_index` (`parentid`(10)),
  KEY `tdo_completed_tasks_completiondate_index` (`completiondate`),
  KEY `tdo_completed_tasks_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_context_assignments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_context_assignments`;

CREATE TABLE `tdo_context_assignments` (
  `taskid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `contextid` varchar(36) DEFAULT NULL,
  `context_assignment_timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_context_assignments_pk` (`taskid`(10),`userid`(10)),
  KEY `tdo_context_assignments_contextid_idx` (`contextid`(10)),
  KEY `tdo_context_assignments_userid_index` (`userid`(10)),
  KEY `tdo_context_assignments_taskid_index` (`taskid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_contexts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_contexts`;

CREATE TABLE `tdo_contexts` (
  `contextid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `name` varchar(72) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `context_timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_contexts_pk` (`contextid`(10)),
  KEY `tdo_contexts_context_timestamp_index` (`context_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

# Dump of table tdo_deleted_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_deleted_tasks`;

CREATE TABLE `tdo_deleted_tasks` (
  `taskid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `note` text,
  `startdate` int(11) NOT NULL DEFAULT '0',
  `duedate` int(11) NOT NULL DEFAULT '0',
  `due_date_has_time` tinyint(1) NOT NULL DEFAULT '0',
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `caldavuri` varchar(255) DEFAULT NULL,
  `caldavdata` blob,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `task_type` int(11) NOT NULL DEFAULT '0',
  `type_data` text,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_userid` varchar(36) DEFAULT NULL,
  `recurrence_type` int(11) NOT NULL DEFAULT '0',
  `advanced_recurrence_string` varchar(255) DEFAULT NULL,
  `project_startdate` int(11) DEFAULT NULL,
  `project_duedate` int(11) DEFAULT NULL,
  `project_duedate_has_time` tinyint(1) DEFAULT NULL,
  `project_priority` int(11) DEFAULT NULL,
  `project_starred` tinyint(1) DEFAULT NULL,
  `location_alert` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_deleted_tasks_pk` (`taskid`(10)),
  KEY `tdo_deleted_tasks_listid_index` (`listid`(10)),
  KEY `tdo_deleted_tasks_parentid_index` (`parentid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_email_notifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_email_notifications`;

CREATE TABLE `tdo_email_notifications` (
  `changeid` varchar(36) NOT NULL,
  `queue_daemon_owner` varchar(5) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_email_notifications_pk` (`changeid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_email_verifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_email_verifications`;

CREATE TABLE `tdo_email_verifications` (
  `verificationid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_email_verifications_pk` (`verificationid`(10)),
  KEY `tdo_email_verifications_userid_idx` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_events
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_events`;

CREATE TABLE `tdo_events` (
  `eventid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `startdate` int(11) NOT NULL DEFAULT '0',
  `enddate` int(11) NOT NULL DEFAULT '0',
  `hastime` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `caldavuri` varchar(255) DEFAULT NULL,
  `caldavdata` blob,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  KEY `tdo_events_pk` (`eventid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_gift_codes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_gift_codes`;

CREATE TABLE `tdo_gift_codes` (
  `gift_code` varchar(36) DEFAULT NULL,
  `subscription_duration` int(11) NOT NULL DEFAULT '0',
  `stripe_gift_payment_id` varchar(36) DEFAULT NULL,
  `purchaser_userid` varchar(36) DEFAULT NULL,
  `purchase_timestamp` int(11) NOT NULL DEFAULT '0',
  `sender_name` varchar(100) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(100) DEFAULT NULL,
  `consumption_date` int(11) NOT NULL DEFAULT '0',
  `consumer_userid` varchar(36) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  KEY `tdo_gift_codes_pk` (`gift_code`(10)),
  KEY `tdo_gift_codes_purchaser_userid` (`purchaser_userid`(10)),
  KEY `tdo_gift_codes_stripe_payment_id` (`stripe_gift_payment_id`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_googleplay_autorenew_tokens
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_googleplay_autorenew_tokens`;

CREATE TABLE `tdo_googleplay_autorenew_tokens` (
  `userid` varchar(36) NOT NULL,
  `product_id` varchar(128) NOT NULL,
  `token` varchar(512) NOT NULL,
  `expiration_date` int(11) NOT NULL DEFAULT '0',
  `autorenewal_canceled` tinyint(1) NOT NULL DEFAULT '0',
  KEY `tdo_googleplay_autorenew_tokens_userid` (`userid`),
  KEY `tdo_googleplay_autorenew_tokens_canceled` (`autorenewal_canceled`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_googleplay_payment_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_googleplay_payment_history`;

CREATE TABLE `tdo_googleplay_payment_history` (
  `userid` varchar(36) NOT NULL,
  `product_id` varchar(128) NOT NULL,
  `purchase_timestamp` int(11) NOT NULL DEFAULT '0',
  `expiration_timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_googleplay_payment_history_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_iap_autorenew_receipts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_iap_autorenew_receipts`;

CREATE TABLE `tdo_iap_autorenew_receipts` (
  `userid` varchar(36) NOT NULL,
  `latest_receipt_data` blob NOT NULL,
  `expiration_date` int(11) NOT NULL DEFAULT '0',
  `transaction_id` varchar(255) NOT NULL,
  `autorenewal_canceled` tinyint(1) NOT NULL DEFAULT '0',
  KEY `tdo_iap_autorenew_receipts_userid` (`userid`),
  KEY `tdo_iap_autorenewal_canceled` (`autorenewal_canceled`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_iap_payment_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_iap_payment_history`;

CREATE TABLE `tdo_iap_payment_history` (
  `userid` varchar(36) NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `purchase_date` varchar(255) NOT NULL,
  `app_item_id` varchar(255) DEFAULT NULL,
  `version_external_identifier` varchar(255) DEFAULT NULL,
  `bid` varchar(64) DEFAULT NULL,
  `bvrs` varchar(36) DEFAULT NULL,
  KEY `tdo_iap_payment_history_userid` (`userid`),
  KEY `tdo_iap_payment_history_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_invitations`;

CREATE TABLE `tdo_invitations` (
  `invitationid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `invited_userid` varchar(36) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `membership_type` int(11) NOT NULL DEFAULT '0',
  `fb_userid` varchar(36) DEFAULT NULL,
  `fb_requestid` varchar(40) DEFAULT NULL,
  KEY `tdo_invitations_pk` (`invitationid`(10)),
  KEY `tdo_invited_user_index` (`invited_userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_list_memberships
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_list_memberships`;

CREATE TABLE `tdo_list_memberships` (
  `listid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `membership_type` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_list_memberships_pk` (`listid`(10),`userid`(10)),
  KEY `tdo_list_membership_userid_index` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_list_settings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_list_settings`;

CREATE TABLE `tdo_list_settings` (
  `listid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `cdavOrder` varchar(7) DEFAULT NULL,
  `cdavColor` varchar(10) DEFAULT NULL,
  `sync_filter_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `task_notifications` tinyint(4) NOT NULL DEFAULT '0',
  `user_notifications` tinyint(4) NOT NULL DEFAULT '0',
  `comment_notifications` tinyint(4) NOT NULL DEFAULT '0',
  `hide_dashboard` tinyint(4) DEFAULT NULL,
  `icon_name` varchar(64) DEFAULT NULL,
  `sort_order` tinyint(4) DEFAULT '0',
  `sort_type` tinyint(4) DEFAULT '0',
  `default_due_date` tinyint(4) DEFAULT '0',
  `notify_assigned_only` tinyint(1) DEFAULT NULL,
  KEY `tdo_list_settings_pk` (`listid`(10),`userid`(10)),
  KEY `tdo_list_settings_userid_idx` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_smart_lists
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_smart_lists`;

CREATE TABLE `tdo_smart_lists` (
`listid` varchar(36) NOT NULL,
`name` varchar(72) DEFAULT NULL,
`userid` varchar(36) NOT NULL,
`color` varchar(20) DEFAULT NULL,
`icon_name` varchar(64) DEFAULT NULL,
`sort_order` tinyint(4) DEFAULT '0',
`json_filter` text,
`sort_type` tinyint(4) DEFAULT '0',
`default_due_date` tinyint(4) DEFAULT '0',
`default_list` varchar(36) DEFAULT NULL,
`excluded_list_ids` text,
`completed_tasks_filter` text,
`deleted` tinyint(1) NOT NULL DEFAULT '0',
`timestamp` int(11) NOT NULL DEFAULT '0',
KEY `tdo_smart_lists_pk` (`listid`(10)),
KEY `tdo_smart_lists_userid_index` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_lists
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_lists`;

CREATE TABLE `tdo_lists` (
  `listid` varchar(36) NOT NULL,
  `name` varchar(72) DEFAULT NULL,
  `description` text,
  `creator` varchar(36) DEFAULT NULL,
  `cdavUri` varchar(255) DEFAULT NULL,
  `cdavTimeZone` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `task_timestamp` int(11) NOT NULL DEFAULT '0',
  `notification_timestamp` int(11) NOT NULL DEFAULT '0',
  `taskito_timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_lists_pk` (`listid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_password_reset
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_password_reset`;

CREATE TABLE `tdo_password_reset` (
  `resetid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_password_reset_pk` (`resetid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_promo_code_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_promo_code_history`;

CREATE TABLE `tdo_promo_code_history` (
  `userid` varchar(36) NOT NULL,
  `subscription_duration` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `creator_userid` varchar(36) NOT NULL,
  `creation_timestamp` int(11) NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  KEY `tdo_promo_code_history_userid` (`userid`),
  KEY `tdo_promo_code_history_creator_userid` (`creator_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_promo_codes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_promo_codes`;

CREATE TABLE `tdo_promo_codes` (
  `promo_code` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `subscription_duration` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `creator_userid` varchar(36) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  KEY `tdo_promo_codes_pk` (`promo_code`(10)),
  KEY `tdo_promo_codes_userid` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_referral_credit_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_referral_credit_history`;

CREATE TABLE `tdo_referral_credit_history` (
  `userid` varchar(36) NOT NULL,
  `consumer_userid` varchar(36) NOT NULL,
  `extension_days` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_referral_credit_history_userid` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_referral_unsubscribers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_referral_unsubscribers`;

CREATE TABLE `tdo_referral_unsubscribers` (
  `email` varchar(100) NOT NULL,
  KEY `tdo_referral_unsubscriber_idx` (`email`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_stripe_gift_payment_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_stripe_gift_payment_history`;

CREATE TABLE `tdo_stripe_gift_payment_history` (
  `stripe_gift_payment_id` varchar(36) DEFAULT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `stripe_userid` varchar(255) NOT NULL,
  `stripe_chargeid` varchar(255) NOT NULL,
  `card_type` varchar(32) NOT NULL,
  `last4` varchar(4) NOT NULL,
  `amount` decimal(10,0) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_stripe_gift_payment_history_pk` (`stripe_gift_payment_id`(10)),
  KEY `tdo_stripe_gift_payment_history_userid` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_stripe_payment_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_stripe_payment_history`;

CREATE TABLE `tdo_stripe_payment_history` (
  `userid` varchar(36) NOT NULL,
  `teamid` varchar(255) DEFAULT NULL,
  `license_count` int(11) NOT NULL DEFAULT '0',
  `stripe_userid` varchar(255) NOT NULL,
  `stripe_chargeid` varchar(255) NOT NULL,
  `card_type` varchar(32) NOT NULL,
  `last4` varchar(4) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `charge_description` varchar(128) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_stripe_payment_history_userid` (`userid`),
  KEY `tdo_stripe_payment_history_stripe_userid` (`stripe_userid`),
  KEY `tdo_stripe_payment_history_timestamp` (`timestamp`),
  KEY `tdo_stripe_payment_history_teamid` (`teamid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_stripe_user_info
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_stripe_user_info`;

CREATE TABLE `tdo_stripe_user_info` (
  `userid` varchar(36) NOT NULL,
  `stripe_userid` varchar(255) NOT NULL,
  KEY `tdo_stripe_user_info_pk` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_subscriptions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_subscriptions`;

CREATE TABLE `tdo_subscriptions` (
  `subscriptionid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `expiration_date` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `level` tinyint(1) NOT NULL DEFAULT '0',
  `teamid` varchar(36) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_subscriptions_pk` (`subscriptionid`(10)),
  KEY `tdo_subscriptions_userid` (`userid`(10)),
  KEY `tdo_subscriptions_expiration_date_idx` (`expiration_date`),
  KEY `tdo_subscriptions_teamid` (`teamid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_system_notifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_system_notifications`;

CREATE TABLE `tdo_system_notifications` (
  `notificationid` varchar(36) NOT NULL,
  `message` text,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `learn_more_url` text,
  KEY `tdo_system_notifications_pk` (`notificationid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_system_settings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_system_settings`;

CREATE TABLE `tdo_system_settings` (
  `setting_id` varchar(255) NOT NULL,
  `setting_value` varchar(512) NOT NULL,
  KEY `tdo_system_settings_setting_id` (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `tdo_system_settings` WRITE;
/*!40000 ALTER TABLE `tdo_system_settings` DISABLE KEYS */;

INSERT INTO `tdo_system_settings` (`setting_id`, `setting_value`)
VALUES
	('GOOGLE_PLAY_CLIENT_SECRET','sDhFZTilJbaqEPKYyhMpKqRX'),
	('GOOGLE_PLAY_CLIENT_ID','443806992058-5f6l7a31ntd7qr8hbr1mkfl113qstbiq.apps.googleusercontent.com'),
	('GOOGLE_PLAY_REFRESH_TOKEN','1/SySK-zrFna4R4_GL7pgKrb4P8fmLh5Bz8mEj5SH9xnE'),
	('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', 'PT5M'),
	('SYSTEM_SETTING_TEAM_IAP_CANCELLATION_REMINDER_DATE_INTERVAL', 'PT5M'),
	('SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL', 'PT5M'),
	('SYSTEM_SETTING_SUBSCRIPTION_LEAD_TIME_IN_SECONDS','30'),
	('SYSTEM_SETTING_SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL_IN_SECONDS','60'),
	('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL','PT5M'),
	('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL','PT10M'),
	('SYSTEM_SETTING_SUBSCRIPTION_TRIAL_DATE_INTERVAL', 'PT5M'),
	('SYSTEM_SETTING_TEAM_GRANDFATHER_DATE', '2015-09-30T23:23:59-06:00');

/*!40000 ALTER TABLE `tdo_system_settings` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tdo_tag_assignments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_tag_assignments`;

CREATE TABLE `tdo_tag_assignments` (
  `tagid` varchar(36) NOT NULL,
  `taskid` varchar(36) NOT NULL,
  KEY `tdo_tag_assignments_pk` (`tagid`(10),`taskid`(10)),
  KEY `tdo_tag_assignments_taskid_index` (`taskid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_tags`;

CREATE TABLE `tdo_tags` (
  `tagid` varchar(36) NOT NULL,
  `name` varchar(72) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  KEY `tdo_tags_pk` (`tagid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_task_notifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_task_notifications`;

CREATE TABLE `tdo_task_notifications` (
  `notificationid` varchar(36) NOT NULL,
  `taskid` varchar(36) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `sound_name` text,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `triggerdate` int(11) NOT NULL DEFAULT '0',
  `triggeroffset` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_task_notifications_pk` (`notificationid`(10)),
  KEY `tdo_task_notifications_taskid_index` (`taskid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_taskitos
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_taskitos`;

CREATE TABLE `tdo_taskitos` (
  `taskitoid` varchar(36) NOT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_taskitos_pk` (`taskitoid`(10)),
  KEY `tdo_taskitos_parentid_index` (`parentid`(10)),
  KEY `tdo_taskitos_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_tasks`;

CREATE TABLE `tdo_tasks` (
  `taskid` varchar(36) NOT NULL,
  `listid` varchar(36) DEFAULT NULL,
  `name` varchar(510) DEFAULT NULL,
  `parentid` varchar(36) DEFAULT NULL,
  `note` text,
  `startdate` int(11) NOT NULL DEFAULT '0',
  `duedate` int(11) NOT NULL DEFAULT '0',
  `due_date_has_time` tinyint(1) NOT NULL DEFAULT '0',
  `completiondate` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `caldavuri` varchar(255) DEFAULT NULL,
  `caldavdata` blob,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `task_type` int(11) NOT NULL DEFAULT '0',
  `type_data` text,
  `starred` tinyint(1) NOT NULL DEFAULT '0',
  `assigned_userid` varchar(36) DEFAULT NULL,
  `recurrence_type` int(11) NOT NULL DEFAULT '0',
  `advanced_recurrence_string` varchar(255) DEFAULT NULL,
  `project_startdate` int(11) DEFAULT NULL,
  `location_alert` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `project_starred` tinyint(1) DEFAULT NULL,
  `project_duedate` int(11) DEFAULT NULL,
  `project_duedate_has_time` tinyint(1) DEFAULT NULL,
  `project_priority` int(11) DEFAULT NULL,
  KEY `tdo_tasks_pk` (`taskid`(10)),
  KEY `tdo_tasks_listid_index` (`listid`(10)),
  KEY `tdo_tasks_parentid_index` (`parentid`(10)),
  KEY `tdo_tasks_duedate_index` (`duedate`),
  KEY `tdo_tasks_timestamp_index` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_team_accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_accounts`;

CREATE TABLE `tdo_team_accounts` (
  `teamid` varchar(36) NOT NULL,
  `teamname` varchar(128) NOT NULL,
  `license_count` int(11) NOT NULL DEFAULT '0',
  `billing_userid` varchar(36) DEFAULT NULL,
  `expiration_date` int(11) NOT NULL DEFAULT '0',
  `creation_date` int(11) NOT NULL DEFAULT '0',
  `modified_date` int(11) NOT NULL DEFAULT '0',
  `billing_frequency` tinyint(1) NOT NULL DEFAULT '0',
  `new_license_count` int(11) NOT NULL DEFAULT '0',
  `biz_name` varchar(128) DEFAULT NULL,
  `biz_phone` varchar(32) DEFAULT NULL,
  `biz_addr1` varchar(128) DEFAULT NULL,
  `biz_addr2` varchar(128) DEFAULT NULL,
  `biz_city` varchar(64) DEFAULT NULL,
  `biz_state` varchar(64) DEFAULT NULL,
  `biz_country` varchar(64) DEFAULT NULL,
  `biz_postal_code` varchar(32) DEFAULT NULL,
  `main_listid` varchar(36) NOT NULL,
  `discovery_answer` varchar(128) DEFAULT NULL,
  KEY `tdo_team_accounts_teamid` (`teamid`(10)),
  KEY `tdo_team_accounts_license_count` (`license_count`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_team_admins
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_admins`;

CREATE TABLE `tdo_team_admins` (
  `teamid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  UNIQUE KEY `tdo_team_admins_uniquekey` (`teamid`(10),`userid`(10)),
  KEY `tdo_team_admins_pk` (`teamid`(10)),
  KEY `tdo_team_admins_userid` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_team_autorenew_history
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_autorenew_history`;

CREATE TABLE `tdo_team_autorenew_history` (
  `teamid` varchar(36) NOT NULL,
  `renewal_attempts` tinyint(4) NOT NULL DEFAULT '0',
  `attempted_time` int(11) NOT NULL DEFAULT '0',
  `failure_reason` varchar(255) DEFAULT NULL,
  KEY `tdo_team_autorenew_history_pk` (`teamid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_team_invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_invitations`;

CREATE TABLE `tdo_team_invitations` (
  `invitationid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `teamid` varchar(36) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `invited_userid` varchar(36) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `membership_type` int(11) NOT NULL DEFAULT '0',
  `fb_userid` varchar(36) DEFAULT NULL,
  `fb_requestid` varchar(40) DEFAULT NULL,
  KEY `tdo_team_invitations_pk` (`invitationid`(10)),
  KEY `tdo_team_invited_user_index` (`invited_userid`(10)),
  KEY `tdo_team_invited_teamid_index` (`teamid`(10)),
  KEY `tdo_team_invited_teamid_email_index` (`teamid`(10),`email`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_team_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_members`;

CREATE TABLE `tdo_team_members` (
  `teamid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  UNIQUE KEY `tdo_team_members_uniquekey` (`teamid`(10),`userid`(10)),
  KEY `tdo_team_members_pk` (`teamid`(10)),
  KEY `tdo_team_members_userid` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_team_subscription_credits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_subscription_credits`;

CREATE TABLE `tdo_team_subscription_credits` (
  `teamid` varchar(36) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `donation_date` int(11) NOT NULL DEFAULT '0',
  `donation_months_count` int(11) NOT NULL DEFAULT '0',
  `consumed_date` int(11) DEFAULT NULL,
  `refunded_date` int(11) DEFAULT NULL,
  KEY `tdo_team_subscription_credits_teamid` (`teamid`(10)),
  KEY `tdo_team_subscription_credits_userid` (`userid`(10)),
  KEY `tdo_team_subscription_credits_donation_date` (`donation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_user_account_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_account_log`;

CREATE TABLE `tdo_user_account_log` (
  `userid` varchar(36) NOT NULL,
  `owner_userid` varchar(36) NOT NULL,
  `change_type` tinyint(4) NOT NULL DEFAULT '0',
  `description` varchar(512) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_user_account_log_userid` (`userid`(10)),
  KEY `tdo_user_account_log_owner_userid` (`owner_userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_user_accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_accounts`;

CREATE TABLE `tdo_user_accounts` (
  `userid` varchar(36) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT NULL,
  `email_opt_out` tinyint(1) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `oauth_provider` tinyint(4) NOT NULL DEFAULT '0',
  `oauth_uid` varchar(36) DEFAULT NULL,
  `first_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `admin_level` tinyint(1) NOT NULL DEFAULT '0',
  `deactivated` tinyint(1) DEFAULT NULL,
  `last_reset_timestamp` int(11) DEFAULT NULL,
  `creation_timestamp` int(11) NOT NULL DEFAULT '0',
  `locale` varchar(10) DEFAULT NULL,
  `best_match_locale` varchar(10) DEFAULT NULL,
  `selected_locale` varchar(10) DEFAULT NULL,
  `image_guid` varchar(36) DEFAULT NULL,
  `image_update_timestamp` int(11) DEFAULT NULL,
  `show_user_messages` text,
  KEY `tdo_user_accounts_username_index` (`username`(10)),
  KEY `tdo_user_accounts_creation_timestamp` (`creation_timestamp`),
  KEY `tdo_user_accounts_pk` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `tdo_user_accounts` WRITE;
/*!40000 ALTER TABLE `tdo_user_accounts` DISABLE KEYS */;

INSERT INTO `tdo_user_accounts` (`userid`, `username`, `email_verified`, `email_opt_out`, `password`, `oauth_provider`, `oauth_uid`, `first_name`, `last_name`, `admin_level`, `deactivated`, `last_reset_timestamp`, `creation_timestamp`, `image_guid`, `image_update_timestamp`)
VALUES
	('56d65b76-616d-9488-5b7a-000020519bbe','pigeon',0,0,'9d1ce632ce21568d9dd2e41f5aa7a149',0,NULL,'','',100,NULL,0,1361573265,'',0);

/*!40000 ALTER TABLE `tdo_user_accounts` ENABLE KEYS */;
UNLOCK TABLES;

# Dump of table tdo_team_integration_slack
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_team_integration_slack`;

CREATE TABLE
`tdo_team_integration_slack` (
`teamid` VARCHAR(36) NOT NULL,
`listid` VARCHAR(36) NOT NULL,
`webhook_url` VARCHAR(128) NOT NULL,
`channel_name` VARCHAR(128) NOT NULL,
`out_token` VARCHAR(128) NOT NULL,
INDEX `tdo_team_integration_slack_teamid`(`teamid`(10)),
INDEX `tdo_team_integration_slack_listid`(`listid`(10)),
UNIQUE `tdo_team_integration_slack_listteam`(`listid`(10),`teamid`(10))
);

# Dump of table tdo_slack_notifications
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_slack_notifications`;

CREATE TABLE `tdo_slack_notifications` (
  `changeid` varchar(36) NOT NULL,
  `queue_daemon_owner` varchar(5) DEFAULT NULL,
  `webhook_url` VARCHAR(128) NOT NULL,
  `payload` text,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_slack_notifications_pk` (`changeid`(10))
);


# Dump of table tdo_user_devices
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_devices`;

CREATE TABLE `tdo_user_devices` (
  `deviceid` varchar(36) NOT NULL,
  `user_deviceid` varchar(80) NOT NULL,
  `userid` varchar(36) NOT NULL,
  `sessionid` varchar(36) NOT NULL,
  `devicetype` varchar(36) NOT NULL,
  `osversion` varchar(36) NOT NULL,
  `appid` varchar(80) NOT NULL,
  `appversion` varchar(36) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `error_number` int(11) NOT NULL DEFAULT '0',
  `error_message` text,
  KEY `tdo_user_devices_deviceid` (`user_deviceid`(10)),
  KEY `tdo_user_devices_userid` (`userid`(10)),
  KEY `tdo_user_devices_sessionid` (`sessionid`(10)),
  KEY `tdo_user_devices_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_user_maintenance
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_maintenance`;

CREATE TABLE `tdo_user_maintenance` (
  `userid` varchar(100) NOT NULL,
  `operation_type` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `daemonid` varchar(36) DEFAULT NULL,
  KEY `tdo_user_maintenance_userid_idx` (`userid`(10)),
  KEY `tdo_user_maintenance_op_type_idx` (`operation_type`),
  KEY `tdo_user_maintenance_daemonid_idx` (`daemonid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_user_migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_migrations`;

CREATE TABLE `tdo_user_migrations` (
  `userid` varchar(36) NOT NULL,
  `daemonid` varchar(36) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `original_subscription_expiration_date` int(11) NOT NULL DEFAULT '0',
  `subscription_time_added` int(11) NOT NULL DEFAULT '0',
  `subscription_expiration_date` int(11) NOT NULL DEFAULT '0',
  `migration_completion_date` int(11) NOT NULL DEFAULT '0',
  `migration_last_attempt` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_user_migrations_username_index` (`username`(10)),
  KEY `tdo_user_migrations_completion_date_index` (`migration_completion_date`),
  KEY `tdo_user_migrations_daemonid_index` (`daemonid`(10)),
  KEY `tdo_user_migrations_pk` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_user_payment_system
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_payment_system`;

CREATE TABLE `tdo_user_payment_system` (
  `userid` varchar(36) NOT NULL,
  `payment_system_type` tinyint(4) NOT NULL DEFAULT '0',
  `payment_system_userid` varchar(36) NOT NULL,
  KEY `tdo_user_payment_system_pk` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_user_referrals
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_referrals`;

CREATE TABLE `tdo_user_referrals` (
  `consumer_userid` varchar(36) NOT NULL,
  `referral_code` varchar(10) NOT NULL,
  `purchase_timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_user_referrals_consumer` (`consumer_userid`(10)),
  KEY `tdo_user_referrals_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table tdo_user_sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_sessions`;

CREATE TABLE `tdo_user_sessions` (
  `sessionid` varchar(36) NOT NULL,
  `userid` varchar(36) DEFAULT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  KEY `tdo_user_sessions_pk` (`sessionid`(10)),
  KEY `tdo_user_sessions_userid_index` (`userid`(10))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Dump of table tdo_user_settings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tdo_user_settings`;

CREATE TABLE `tdo_user_settings` (
  `userid` varchar(36) NOT NULL,
  `timezone` varchar(36) DEFAULT NULL,
  `user_inbox` varchar(36) DEFAULT NULL,
  `tag_filter_with_and` tinyint(1) NOT NULL DEFAULT '0',
  `task_sort_order` int(11) NOT NULL DEFAULT '0',
  `start_date_filter` int(10) unsigned DEFAULT NULL,
  `focus_show_undue_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `focus_show_starred_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `focus_show_completed_date` int(11) NOT NULL DEFAULT '0',
  `focus_hide_task_date` int(11) NOT NULL DEFAULT '2',
  `focus_hide_task_priority` int(11) NOT NULL DEFAULT '0',
  `focus_list_filter_string` text,
  `focus_show_subtasks` tinyint(1) DEFAULT NULL,
  `focus_ignore_start_dates` tinyint(1) DEFAULT NULL,
  `task_creation_email` varchar(50) DEFAULT NULL,
  `referral_code` varchar(10) DEFAULT NULL,
  `all_list_hide_dashboard` tinyint(4) DEFAULT NULL,
  `starred_list_hide_dashboard` tinyint(4) DEFAULT NULL,
  `focus_list_hide_dashboard` tinyint(4) DEFAULT NULL,
  `all_list_filter_string` text,
  `default_duedate` tinyint(4) DEFAULT NULL,
  `show_overdue_section` tinyint(1) DEFAULT NULL,
  `skip_task_date_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_priority_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_list_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_context_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_tag_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_checklist_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_project_parsing` tinyint(1) DEFAULT NULL,
  `skip_task_startdate_parsing` tinyint(1) DEFAULT NULL,
  `new_feature_flags` bigint(20) unsigned DEFAULT NULL,
  `email_notification_defaults` int(10) unsigned DEFAULT NULL,
  `enable_google_analytics_tracking` tinyint(1) unsigned DEFAULT '1',
  `default_list` varchar(36) DEFAULT NULL,
  UNIQUE KEY `tdo_user_settings_task_creation_email` (`task_creation_email`),
  KEY `tdo_user_settings_pk` (`userid`(10)),
  KEY `tdo_user_settings_referral_code_idx` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `tdo_user_settings` WRITE;
/*!40000 ALTER TABLE `tdo_user_settings` DISABLE KEYS */;

INSERT INTO `tdo_user_settings` (`userid`, `timezone`, `user_inbox`, `tag_filter_with_and`, `task_sort_order`, `start_date_filter`, `focus_show_undue_tasks`, `focus_show_starred_tasks`, `focus_show_completed_date`, `focus_hide_task_date`, `focus_hide_task_priority`, `focus_list_filter_string`, `focus_show_subtasks`, `focus_ignore_start_dates`, `task_creation_email`, `referral_code`, `all_list_hide_dashboard`, `starred_list_hide_dashboard`, `focus_list_hide_dashboard`, `all_list_filter_string`, `default_duedate`, `show_overdue_section`, `skip_task_date_parsing`, `skip_task_priority_parsing`, `skip_task_list_parsing`, `skip_task_context_parsing`, `skip_task_tag_parsing`, `skip_task_checklist_parsing`, `skip_task_project_parsing`, `skip_task_startdate_parsing`, `new_feature_flags`, `email_notification_defaults`, `enable_google_analytics_tracking`, `default_list`)
VALUES
	('56d65b76-616d-9488-5b7a-000020519bbe','America/Boise','1c83c1f6-1f39-3608-4af6-0000341ea586',0,0,NULL,0,0,0,2,0,NULL,0,NULL,NULL,NULL,0,0,0,NULL,0,0,0,0,0,0,0,0,0,NULL,0,NULL,1,NULL);

/*!40000 ALTER TABLE `tdo_user_settings` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

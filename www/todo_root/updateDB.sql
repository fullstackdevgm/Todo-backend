#
# Production Servers - www.todo-cloud.com (Everything below this has been applied)
#

# Boyd - Added the following to the production database on 9 May 2017

ALTER TABLE tdo_user_settings ADD COLUMN default_list VARCHAR(36) DEFAULT NULL;

# Boyd - Added the following to the production database on 20 May 2016

ALTER TABLE tdo_user_settings ADD COLUMN enable_google_analytics_tracking TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE tdo_list_settings ADD COLUMN sort_type TINYINT(4) DEFAULT 0 AFTER sort_order;
ALTER TABLE tdo_list_settings ADD COLUMN default_due_date TINYINT(4) DEFAULT 0 AFTER sort_type;

ALTER TABLE tdo_user_accounts ADD COLUMN show_user_messages text;
ALTER TABLE tdo_user_accounts ADD COLUMN locale VARCHAR(10) NULL AFTER creation_timestamp;
ALTER TABLE tdo_user_accounts ADD COLUMN best_match_locale VARCHAR(10) NULL AFTER locale;
ALTER TABLE tdo_user_accounts ADD COLUMN selected_locale VARCHAR(10) NULL AFTER best_match_locale;

CREATE TABLE `tdo_smart_lists` (`listid` varchar(36) NOT NULL, `name` varchar(72) DEFAULT NULL, `userid` varchar(36) NOT NULL, `color` varchar(20) DEFAULT NULL, `icon_name` varchar(64) DEFAULT NULL, `sort_order` tinyint(4) DEFAULT '0', `json_filter` text, `sort_type` tinyint(4) DEFAULT '0', `default_due_date` tinyint(4) DEFAULT '0', `default_list` varchar(36) DEFAULT NULL, `excluded_list_ids` text, `completed_tasks_filter` text, `deleted` tinyint(1) NOT NULL DEFAULT '0', `timestamp` int(11) NOT NULL DEFAULT '0', KEY `tdo_smart_lists_pk` (`listid`(10)), KEY `tdo_smart_lists_userid_index` (`userid`(10)));

# Boyd - Added this to support archiving tasks - 1 April 2016
ALTER TABLE tdo_archived_tasks ADD COLUMN startdate INT NOT NULL DEFAULT 0 AFTER note;
ALTER TABLE tdo_archived_tasks ADD COLUMN project_startdate INT AFTER advanced_recurrence_string;

# Boyd - I added this to the production system on 12/3/2015 as part of the v2.4.1.15 rollout
CREATE TABLE `tdo_slack_notifications` (`changeid` varchar(36) NOT NULL,`queue_daemon_owner` varchar(5) DEFAULT NULL,`webhook_url` VARCHAR(128) NOT NULL,`payload` text,`timestamp` int(11) NOT NULL DEFAULT '0',KEY `tdo_slack_notifications_pk` (`changeid`(10)));
CREATE TABLE tdo_team_integration_slack (teamid VARCHAR(36) NOT NULL, listid VARCHAR(36) NOT NULL, webhook_url VARCHAR(128) NOT NULL, channel_name VARCHAR(128) NOT NULL, INDEX tdo_team_integration_slack_teamid(teamid(10)), INDEX tdo_team_integration_slack_listid(listid(10)), UNIQUE tdo_team_integration_slack_listchannel(listid(10),channel_name(10)));

# Boyd - I added this to the production system on 10/28/2015 as part of the v2.4.0.27 rollout
CREATE TABLE tdo_team_subscription_credits(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, donation_date INT NOT NULL DEFAULT 0, donation_months_count TINYINT NOT NULL DEFAULT 0, consumed_date INT DEFAULT NULL, refunded_date INT DEFAULT NULL, INDEX tdo_team_subscription_credits_teamid(teamid(10)), INDEX tdo_team_subscription_credits_userid(userid(10)), INDEX tdo_team_subscription_credits_donation_date(donation_date));

ALTER TABLE tdo_team_accounts ADD COLUMN main_listid VARCHAR(36) NULL AFTER biz_postal_code;
ALTER TABLE tdo_team_accounts ADD COLUMN discovery_answer VARCHAR(128) NULL AFTER main_listid;
ALTER TABLE tdo_team_admins ADD CONSTRAINT tdo_team_admins_uniquekey UNIQUE(teamid,userid);
ALTER TABLE tdo_team_admins DROP KEY tdo_team_admins_fullkey;
ALTER TABLE tdo_team_members ADD CONSTRAINT tdo_team_members_uniquekey UNIQUE(teamid,userid);
ALTER TABLE tdo_team_members DROP KEY tdo_team_members_fullkey;

# Boyd - I added this index on 8/31/2015 to our production database server
CREATE INDEX tdo_user_devices_timestamp ON tdo_user_devices(timestamp);


#
# Database Cleanup Scripts
#
/* select * from tdo_context_assignments where context_assignment_timestamp > unix_timestamp('2013-08-01 00:00:01');  */
create table tdo_context_assignments_tmp like tdo_context_assignments; 
alter table tdo_context_assignments_tmp disable keys; 
insert into tdo_context_assignments_tmp
    	select * from tdo_context_assignments where context_assignment_timestamp > unix_timestamp('2013-08-01 00:00:01'); 
alter table tdo_context_assignments_tmp enable keys; 
drop table tdo_context_assignments; 
alter table tdo_context_assignments_tmp rename tdo_context_assignments;

/* select count(1) from tdo_completed_tasks  where completiondate > unix_timestamp('2013-08-01 00:00:01') */
create table tdo_completed_tasks_tmp like tdo_completed_tasks; 
alter table tdo_completed_tasks_tmp disable keys; 
insert into tdo_completed_tasks_tmp
    	select * from tdo_completed_tasks where completiondate > unix_timestamp('2013-08-01 00:00:01'); 
alter table tdo_completed_tasks_tmp enable keys; 
drop table tdo_completed_tasks; 
alter table tdo_completed_tasks_tmp rename tdo_completed_tasks;

/* select count(1) from tdo_change_log where mod_date > unix_timestamp('2013-08-01 00:00:01') */
create table tdo_change_log_tmp like tdo_change_log; 
alter table tdo_change_log_tmp disable keys; 
insert into tdo_change_log_tmp
    	select * from tdo_change_log where mod_date > unix_timestamp('2013-08-01 00:00:01'); 
alter table tdo_change_log_tmp enable keys; 
drop table tdo_change_log; 
alter table tdo_change_log_tmp rename tdo_change_log;



#TODO Need to add in all the new tables needed for team accounts

#
#
# DEV SERVER - plano.appigo.com (Everything below this has been applied)
#




#
# PRODUCTION SERVER - www.todo-cloud.com (Everything below this has been applied)
#
#
#dave - applied on todo-cloud.com on 2- Sept 2014 at 11:58 PM Mountain time
ALTER TABLE tdo_subscriptions ADD COLUMN teamid VARCHAR(36) NULL after level;
CREATE TABLE tdo_team_accounts(teamid VARCHAR(36) NOT NULL, teamname VARCHAR(128) NOT NULL, license_count INT NOT NULL DEFAULT 0, billing_userid VARCHAR(36) NULL, expiration_date INT NOT NULL DEFAULT 0, creation_date INT NOT NULL DEFAULT 0, modified_date INT NOT NULL DEFAULT 0, billing_frequency TINYINT(1) NOT NULL DEFAULT 0, new_license_count INT NOT NULL DEFAULT 0, biz_name VARCHAR(128) NULL, biz_phone VARCHAR(32) NULL, biz_addr1 VARCHAR(128) NULL, biz_addr2 VARCHAR(128) NULL, biz_city VARCHAR(64) NULL, biz_state VARCHAR(64) NULL, biz_country VARCHAR(64) NULL, biz_postal_code VARCHAR(32) NULL, INDEX tdo_team_accounts_teamid (teamid(10)), INDEX tdo_team_accounts_license_count (license_count), INDEX tdo_team_accounts_billing_userid (billing_userid(10)));
CREATE TABLE tdo_team_admins(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, INDEX tdo_team_admins_pk(teamid(10)), INDEX tdo_team_admins_fullkey(teamid(10),userid(10)), INDEX tdo_team_admins_userid(userid(10)));
CREATE TABLE tdo_team_members(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, INDEX tdo_team_members_pk(teamid(10)), INDEX tdo_team_members_fullkey(teamid(10),userid(10)), INDEX tdo_team_members_userid(userid(10)));
CREATE TABLE tdo_team_invitations(invitationid VARCHAR(36) NOT NULL, userid VARCHAR(36), teamid VARCHAR(36), email VARCHAR(100), invited_userid VARCHAR(36), timestamp INT NOT NULL DEFAULT 0, membership_type INT NOT NULL DEFAULT 0, fb_userid VARCHAR(36), fb_requestid VARCHAR(40), INDEX tdo_team_invitations_pk(invitationid(10)), INDEX tdo_team_invited_user_index(invited_userid(10)), INDEX tdo_team_invited_teamid_index(teamid(10)), INDEX tdo_team_invited_teamid_email_index(teamid(10),email(10)));
CREATE TABLE tdo_team_autorenew_history(teamid VARCHAR(36) NOT NULL, renewal_attempts TINYINT NOT NULL DEFAULT 0, attempted_time INT NOT NULL DEFAULT 0, failure_reason VARCHAR(255), INDEX tdo_team_autorenew_history_pk(teamid(10)));

ALTER TABLE tdo_stripe_payment_history ADD COLUMN teamid VARCHAR(255) NULL after userid;
ALTER TABLE tdo_stripe_payment_history ADD COLUMN license_count INT NOT NULL DEFAULT 0 after teamid;
ALTER TABLE tdo_stripe_payment_history ADD COLUMN charge_description VARCHAR(128) after amount;
CREATE INDEX tdo_stripe_payment_history_teamid on tdo_stripe_payment_history(teamid(10));
CREATE INDEX tdo_subscriptions_teamid on tdo_subscriptions(teamid(10));



#boyd - applied on www.todo-cloud.com on 05 Oct 2013 at 3:25 PM Mountain Time
CREATE INDEX tdo_subscriptions_expiration_date_idx on tdo_subscriptions(expiration_date);

#boyd - applied on www.todo-cloud.com on 05 Oct 2013 at 3:05 PM Mountain Time
CREATE INDEX tdo_email_verifications_userid_idx ON tdo_email_verifications(userid(10));

#boyd - applied on www.todo-cloud.com on 05 Oct 2013 at 12:47 PM Mountain Time
CREATE INDEX tdo_list_settings_userid_idx on tdo_list_settings(userid(10));

#calvin - this is for the appigo login stuff
# applied to production on 01 Oct 2013
CREATE TABLE appigo_user_accounts(userid VARCHAR(36) NOT NULL, username VARCHAR(100), email_verified TINYINT(1), email_opt_out TINYINT(1), password VARCHAR(64), oauth_provider TINYINT NOT NULL DEFAULT 0, oauth_uid VARCHAR(36), first_name VARCHAR(60), last_name VARCHAR(60), admin_level TINYINT(1) NOT NULL DEFAULT 0, deactivated TINYINT(1), last_reset_timestamp INT, creation_timestamp INT NOT NULL DEFAULT 0, image_guid VARCHAR(36), image_update_timestamp INT, INDEX tdo_user_accounts_username_index (username(10)), INDEX tdo_user_accounts_creation_timestamp (creation_timestamp), INDEX tdo_user_accounts_pk(userid(10)));
CREATE TABLE appigo_email_verifications(verificationid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_email_verifications_pk(verificationid(10)));
CREATE TABLE appigo_password_reset(resetid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_password_reset_pk(resetid(10)));
CREATE TABLE appigo_user_account_log(userid VARCHAR(36) NOT NULL, owner_userid VARCHAR(36) NOT NULL, change_type TINYINT NOT NULL DEFAULT 0, description VARCHAR(512) NOT NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_account_log_userid(userid(10)), INDEX tdo_user_account_log_owner_userid(owner_userid(10)));
CREATE TABLE appigo_email_list_user(email VARCHAR(100) NOT NULL, last_source TINYINT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX appigo_email_idx(email(15)));


#boyd - applied on www.todo-cloud.com on 01 Oct 2013
#       Reducing the time taken on the server for doing queries like this:
#       UPDATE tdo_deleted_tasks SET listid='4a7104be-0527-dca9-7a7c-000035f9e809' WHERE parentid='4c1b85b3-65d4-e7e8-1b06-000004c6181e';
CREATE INDEX tdo_deleted_tasks_parentid_index on tdo_deleted_tasks(parentid(10));

#boyd - applied to www.todo-cloud.com on 27 Aug 2013
ALTER TABLE tdo_list_settings ADD COLUMN icon_name VARCHAR(64) AFTER hide_dashboard;
ALTER TABLE tdo_list_settings ADD COLUMN sort_order TINYINT DEFAULT 0 AFTER icon_name;

#boyd - applied to www.todo-cloud.com on 10 Jul 2013
ALTER TABLE tdo_invitations ADD COLUMN invited_userid VARCHAR(36) AFTER email;
CREATE INDEX tdo_invited_user_index on tdo_invitations(invited_userid(10));

#boyd - applied to www.todo-cloud.com on 26 Jun 2013
ALTER TABLE tdo_user_settings ADD COLUMN start_date_filter INT UNSIGNED AFTER task_sort_order;
ALTER TABLE tdo_user_settings ADD COLUMN focus_ignore_start_dates TINYINT(1) AFTER focus_show_subtasks;

CREATE TABLE tdo_system_settings(setting_id VARCHAR(255) NOT NULL, setting_value VARCHAR(512) NOT NULL, INDEX tdo_system_settings_setting_id (setting_id));
CREATE TABLE tdo_googleplay_payment_history(userid VARCHAR(36) NOT NULL, product_id VARCHAR(128) NOT NULL, purchase_timestamp INT NOT NULL DEFAULT 0, expiration_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_googleplay_payment_history_userid (userid));
CREATE TABLE tdo_googleplay_autorenew_tokens(userid VARCHAR(36) NOT NULL, product_id VARCHAR(128) NOT NULL, token VARCHAR(512) NOT NULL, expiration_date INT NOT NULL DEFAULT 0, autorenewal_canceled TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_googleplay_autorenew_tokens_userid (userid), INDEX tdo_googleplay_autorenew_tokens_canceled (autorenewal_canceled));
INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_REFRESH_TOKEN', '1/SySK-zrFna4R4_GL7pgKrb4P8fmLh5Bz8mEj5SH9xnE');
INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_CLIENT_ID', '443806992058-5f6l7a31ntd7qr8hbr1mkfl113qstbiq.apps.googleusercontent.com');
INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_CLIENT_SECRET', 'sDhFZTilJbaqEPKYyhMpKqRX');


#boyd - applied to www.todo-cloud.com on 05 Apr 2013

ALTER TABLE tdo_user_settings ADD COLUMN email_notification_defaults INT UNSIGNED;

ALTER TABLE tdo_user_settings ADD COLUMN skip_task_startdate_parsing TINYINT(1) AFTER skip_task_project_parsing;

ALTER TABLE tdo_tasks ADD COLUMN startdate INT NOT NULL DEFAULT 0 AFTER note;
ALTER TABLE tdo_tasks ADD COLUMN project_startdate INT AFTER advanced_recurrence_string;
ALTER TABLE tdo_completed_tasks ADD COLUMN startdate INT NOT NULL DEFAULT 0 AFTER note;
ALTER TABLE tdo_completed_tasks ADD COLUMN project_startdate INT AFTER advanced_recurrence_string;
ALTER TABLE tdo_deleted_tasks ADD COLUMN startdate INT NOT NULL DEFAULT 0 AFTER note;
ALTER TABLE tdo_deleted_tasks ADD COLUMN project_startdate INT AFTER advanced_recurrence_string;


# this was already there!

CREATE TABLE tdo_iap_autorenew_receipts (userid VARCHAR(36) NOT NULL, latest_receipt_data BLOB NOT NULL, expiration_date INT NOT NULL DEFAULT 0, transaction_id VARCHAR(255) NOT NULL, autorenewal_canceled TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_iap_autorenew_receipts_userid (userid), INDEX tdo_iap_autorenewal_canceled (autorenewal_canceled));


#boyd - applied to www.todo-cloud.com on 31 Jan 2013

ALTER TABLE tdo_user_settings ADD new_feature_flags BIGINT UNSIGNED;
ALTER TABLE tdo_tags MODIFY name VARCHAR(72) COLLATE latin1_general_cs;
CREATE TABLE tdo_user_referrals (consumer_userid VARCHAR(36) NOT NULL, referral_code VARCHAR(10) NOT NULL, purchase_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_referrals_consumer(consumer_userid(10)), INDEX tdo_user_referrals_code(referral_code(10)));
ALTER TABLE tdo_user_settings ADD referral_code VARCHAR(10) AFTER task_creation_email;
CREATE INDEX tdo_user_settings_referral_code_idx ON tdo_user_settings(referral_code(10));
CREATE TABLE tdo_referral_unsubscribers (email VARCHAR(100) NOT NULL, INDEX tdo_referral_unsubscriber_idx(email(10)));
CREATE TABLE tdo_referral_credit_history (userid VARCHAR(36) NOT NULL, consumer_userid VARCHAR(36) NOT NULL, extension_days INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_referral_credit_history_userid (userid(10)));


#boyd - applied to www.todo-cloud.com on 10 Jan 2013

ALTER TABLE tdo_user_settings ADD skip_task_date_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_priority_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_list_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_context_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_tag_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_checklist_parsing TINYINT(1);
ALTER TABLE tdo_user_settings ADD skip_task_project_parsing TINYINT(1);

#boyd - applied to www.todo-cloud.com on 20 Dec 2012 @ 9:45 PM MST
CREATE TABLE tdo_gift_codes (gift_code VARCHAR(36), subscription_duration INT NOT NULL DEFAULT 0, stripe_gift_payment_id VARCHAR(36), purchaser_userid VARCHAR(36), purchase_timestamp INT NOT NULL DEFAULT 0, sender_name VARCHAR(100), recipient_name VARCHAR(100), recipient_email VARCHAR(100), consumption_date INT NOT NULL DEFAULT 0, consumer_userid VARCHAR(36), message VARCHAR(255), INDEX tdo_gift_codes_pk(gift_code(10)), INDEX tdo_gift_codes_purchaser_userid(purchaser_userid(10)), INDEX tdo_gift_codes_stripe_payment_id(stripe_gift_payment_id(10)) );
CREATE TABLE tdo_stripe_gift_payment_history (stripe_gift_payment_id VARCHAR(36), userid VARCHAR(36), stripe_userid VARCHAR(255) NOT NULL, stripe_chargeid VARCHAR(255) NOT NULL, card_type VARCHAR(32) NOT NULL, last4 VARCHAR(4) NOT NULL, amount DECIMAL NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_stripe_gift_payment_history_pk(stripe_gift_payment_id(10)), INDEX tdo_stripe_gift_payment_history_userid(userid(10)) );
ALTER TABLE tdo_user_settings ADD show_overdue_section TINYINT(1);

#boyd - applied to www.todo-cloud.com on 19 Nov 2012 @ 2:30 PM MST
#boyd - applied to plano on 17 Nov 2012 @ 2:55 PM MST
CREATE TABLE tdo_system_notifications(notificationid VARCHAR(36) NOT NULL, message TEXT, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_system_notifications_pk(notificationid(10)));
ALTER TABLE tdo_system_notifications ADD learn_more_url TEXT;

#boyd - applied to www.todo-cloud.com on 17 Nov 2012 @ 11:59 PM MST
#boyd - applied to plano on 16 Nov 2012 @ 3:34 PM Mountain Time
CREATE INDEX tdo_context_assignments_contextid_idx ON tdo_context_assignments(contextid(10));
OPTIMIZE TABLE tdo_context_assignments;


# New tables to hold archived tasks
CREATE TABLE tdo_archived_tasks(taskid VARCHAR(36) NOT NULL, listid VARCHAR(36), name VARCHAR(510), parentid VARCHAR(36), note TEXT, duedate INT NOT NULL DEFAULT 0, due_date_has_time TINYINT(1) NOT NULL DEFAULT 0, completiondate INT NOT NULL DEFAULT 0, priority INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, caldavuri VARCHAR(255), caldavdata BLOB, deleted TINYINT(1) NOT NULL DEFAULT 0, task_type INT NOT NULL DEFAULT 0, type_data TEXT, starred TINYINT(1) NOT NULL DEFAULT 0, assigned_userid VARCHAR(36), recurrence_type INT NOT NULL DEFAULT 0, advanced_recurrence_string VARCHAR(255), project_duedate INT, project_duedate_has_time TINYINT(1), project_priority INT, project_starred TINYINT(1), location_alert TEXT, sort_order INT NOT NULL DEFAULT 0, INDEX tdo_archived_tasks_pk(taskid(10)));
CREATE INDEX tdo_archived_tasks_listid_index on tdo_archived_tasks(listid(10));
CREATE INDEX tdo_archived_tasks_timestamp_index on tdo_archived_tasks(timestamp);

CREATE TABLE tdo_archived_taskitos(taskitoid VARCHAR(36) NOT NULL, parentid VARCHAR(36), name VARCHAR(510), completiondate INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, INDEX tdo_archived_taskitos_pk(taskitoid(10)));
CREATE INDEX tdo_archived_taskitos_parentid_index on tdo_archived_taskitos(parentid(10));
CREATE INDEX tdo_archived_taskitos_timestamp_index on tdo_archived_taskitos(timestamp);


CREATE TABLE tdo_user_maintenance(userid VARCHAR(100) NOT NULL, operation_type INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, daemonid VARCHAR(36), INDEX tdo_user_maintenance_userid_idx(userid(10)), INDEX tdo_user_maintenance_op_type_idx(operation_type), INDEX tdo_user_maintenance_daemonid_idx(daemonid(10)));



#calvin - applied to plano and production on 11/10/2012 TAG- TODO_PRO_20121110_1
#CRG - Optimizing the time stamps for sync by storing them on the list
ALTER TABLE tdo_lists ADD task_timestamp INT NOT NULL DEFAULT 0;
ALTER TABLE tdo_lists ADD notification_timestamp INT NOT NULL DEFAULT 0;
ALTER TABLE tdo_lists ADD taskito_timestamp INT NOT NULL DEFAULT 0;





#boyd - applied to plano and production on 11/8/2012
DROP INDEX tdo_user_devices_deviceid ON tdo_user_devices;
CREATE INDEX tdo_user_devices_deviceid ON tdo_user_devices(user_deviceid(10));

DROP INDEX tdo_user_devices_userid ON tdo_user_devices;
CREATE INDEX tdo_user_devices_userid ON tdo_user_devices(userid(10));

DROP INDEX tdo_user_devices_sessionid ON tdo_user_devices;
CREATE INDEX tdo_user_devices_sessionid ON tdo_user_devices(sessionid(10));

OPTIMIZE TABLE tdo_user_devices;


#boyd - applied to plano on 11/04/2012
#boyd - appliet to production on 11/05/2012
CREATE TABLE tdo_bounced_emails(email VARCHAR(100) NOT NULL, bounce_type INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, bounce_count INT NOT NULL DEFAULT 0, INDEX tdo_bounced_emails_email(email(10)));


#calvin - appied to pilot 10/17/2012

ALTER TABLE tdo_user_accounts ADD COLUMN image_guid VARCHAR(36) AFTER creation_timestamp;
ALTER TABLE tdo_user_accounts ADD COLUMN image_update_timestamp INT AFTER image_guid;

#calvin - applied to pilot 10/10/2012
#calvin - applied to plano 10/10/2012
ALTER TABLE tdo_user_accounts ADD COLUMN email_opt_out TINYINT(1) AFTER email_verified;


#calvin - applied to production on 10/4/2012
#NCB - applied to plano 10/4/12
#NCB - increasing the size of all our text fields to better accommodate double byte languages such as Russian
ALTER TABLE tdo_user_accounts MODIFY username VARCHAR(100);
ALTER TABLE tdo_user_accounts MODIFY password VARCHAR(64);
ALTER TABLE tdo_user_accounts MODIFY first_name VARCHAR(60);
ALTER TABLE tdo_user_accounts MODIFY last_name VARCHAR(60);
ALTER TABLE tdo_user_migrations MODIFY username VARCHAR(100);
ALTER TABLE tdo_user_migrations MODIFY password VARCHAR(64);
ALTER TABLE tdo_email_verifications MODIFY username VARCHAR(100);
ALTER TABLE tdo_tasks MODIFY name VARCHAR(510);
ALTER TABLE tdo_completed_tasks MODIFY name VARCHAR(510);
ALTER TABLE tdo_deleted_tasks MODIFY name VARCHAR(510);
ALTER TABLE tdo_taskitos MODIFY name VARCHAR(510);
ALTER TABLE tdo_lists MODIFY name VARCHAR(72);
ALTER TABLE tdo_contexts MODIFY name VARCHAR(72);
ALTER TABLE tdo_tags MODIFY name VARCHAR(72);
ALTER TABLE tdo_change_log MODIFY item_name VARCHAR(72);
ALTER TABLE tdo_invitations MODIFY email VARCHAR(100);
ALTER TABLE tdo_password_reset MODIFY username VARCHAR(100);
ALTER TABLE tdo_comments MODIFY item_name VARCHAR(72);


#boyd - applied to production on 10/01/2012
#calvin - applied to plano 9/20/2012
ALTER TABLE tdo_user_settings ADD all_list_filter_string TEXT;
ALTER TABLE tdo_user_settings ADD default_duedate TINYINT;
ALTER TABLE tdo_user_accounts ADD last_reset_timestamp INT;

#calvin - applied to production on 9/19/2012
ALTER TABLE tdo_list_settings DROP COLUMN list_notifications;
ALTER TABLE tdo_list_settings DROP COLUMN invitation_notifications;
ALTER TABLE tdo_list_settings ADD notify_assigned_only TINYINT(1);



#calvin - applied to plano on 9/14/2012
#calvin - applied to production on 9/14/2012

#create deleted and completed task tables
CREATE TABLE tdo_completed_tasks(taskid VARCHAR(36) PRIMARY KEY, listid VARCHAR(36), name VARCHAR(255), parentid VARCHAR(36), note TEXT, duedate INT NOT NULL DEFAULT 0, due_date_has_time TINYINT(1) NOT NULL DEFAULT 0, completiondate INT NOT NULL DEFAULT 0, priority INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, caldavuri VARCHAR(255), caldavdata BLOB, deleted TINYINT(1) NOT NULL DEFAULT 0, task_type INT NOT NULL DEFAULT 0, type_data TEXT, starred TINYINT(1) NOT NULL DEFAULT 0, assigned_userid VARCHAR(36), recurrence_type INT NOT NULL DEFAULT 0, advanced_recurrence_string VARCHAR(255), project_duedate INT, project_duedate_has_time TINYINT(1), project_priority INT, project_starred TINYINT(1), location_alert TEXT, sort_order INT NOT NULL DEFAULT 0);

CREATE TABLE tdo_deleted_tasks(taskid VARCHAR(36) PRIMARY KEY, listid VARCHAR(36), name VARCHAR(255), parentid VARCHAR(36), note TEXT, duedate INT NOT NULL DEFAULT 0, due_date_has_time TINYINT(1) NOT NULL DEFAULT 0, completiondate INT NOT NULL DEFAULT 0, priority INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, caldavuri VARCHAR(255), caldavdata BLOB, deleted TINYINT(1) NOT NULL DEFAULT 0, task_type INT NOT NULL DEFAULT 0, type_data TEXT, starred TINYINT(1) NOT NULL DEFAULT 0, assigned_userid VARCHAR(36), recurrence_type INT NOT NULL DEFAULT 0, advanced_recurrence_string VARCHAR(255), project_duedate INT, project_duedate_has_time TINYINT(1), project_priority INT, project_starred TINYINT(1), location_alert TEXT, sort_order INT NOT NULL DEFAULT 0);

INSERT INTO tdo_completed_tasks(taskid,listid,name,parentid,note,duedate,due_date_has_time,completiondate,priority,timestamp,caldavuri,caldavdata,deleted,task_type,type_data,starred,assigned_userid,recurrence_type,advanced_recurrence_string,project_duedate,project_duedate_has_time,project_priority,project_starred,location_alert,sort_order) SELECT taskid,listid,name,parentid,note,duedate,due_date_has_time,completiondate,priority,timestamp,caldavuri,caldavdata,deleted,task_type,type_data,starred,assigned_userid,recurrence_type,advanced_recurrence_string,project_duedate,project_duedate_has_time,project_priority,project_starred,location_alert,sort_order FROM tdo_tasks WHERE completiondate != 0;

DELETE FROM tdo_tasks WHERE completiondate != 0;

CREATE INDEX tdo_completed_tasks_listid_index on tdo_completed_tasks(listid);
CREATE INDEX tdo_completed_tasks_parentid_index on tdo_completed_tasks(parentid);
CREATE INDEX tdo_completed_tasks_duedate_index on tdo_completed_tasks(duedate);
CREATE INDEX tdo_completed_tasks_completiondate_index on tdo_completed_tasks(completiondate);
CREATE INDEX tdo_completed_tasks_priority_index on tdo_completed_tasks(priority);
CREATE INDEX tdo_completed_tasks_assigned_userid_index on tdo_completed_tasks(assigned_userid);
CREATE INDEX tdo_completed_tasks_starred_index on tdo_completed_tasks(starred);
CREATE INDEX tdo_completed_tasks_deleted_index on tdo_completed_tasks(deleted);
CREATE INDEX tdo_completed_tasks_timestamp_index on tdo_completed_tasks(timestamp);

INSERT INTO tdo_deleted_tasks(taskid,listid,name,parentid,note,duedate,due_date_has_time,completiondate,priority,timestamp,caldavuri,caldavdata,deleted,task_type,type_data,starred,assigned_userid,recurrence_type,advanced_recurrence_string,project_duedate,project_duedate_has_time,project_priority,project_starred,location_alert,sort_order) SELECT taskid,listid,name,parentid,note,duedate,due_date_has_time,completiondate,priority,timestamp,caldavuri,caldavdata,deleted,task_type,type_data,starred,assigned_userid,recurrence_type,advanced_recurrence_string,project_duedate,project_duedate_has_time,project_priority,project_starred,location_alert,sort_order FROM tdo_tasks WHERE deleted != 0;

DELETE FROM tdo_tasks WHERE deleted != 0;

CREATE INDEX tdo_deleted_tasks_listid_index on tdo_deleted_tasks(listid);









#calvin - applied to pilot on 9/7/2012
#calvin - applied to plano on 9/7/2012
CREATE TABLE tdo_user_devices(deviceid VARCHAR(36) NOT NULL, user_deviceid VARCHAR(80) NOT NULL, userid VARCHAR(36) NOT NULL, sessionid VARCHAR(36) NOT NULL, devicetype VARCHAR(36) NOT NULL, osversion VARCHAR(36) NOT NULL, appid VARCHAR(80) NOT NULL, appversion VARCHAR(36) NOT NULL, timestamp INT NOT NULL DEFAULT 0, error_number INT NOT NULL DEFAULT 0, error_message TEXT,  INDEX tdo_user_devices_deviceid(user_deviceid), INDEX tdo_user_devices_userid(userid), INDEX tdo_user_devices_sessionid(sessionid));

#calvin - applied to pilot on 9/7/2012
#nicole - applied to plano on 9/5/2012
ALTER TABLE tdo_user_accounts ADD COLUMN email_verified TINYINT(1) AFTER username;
CREATE TABLE tdo_email_verifications(verificationid VARCHAR(36) PRIMARY KEY, userid VARCHAR(36), username VARCHAR(50), timestamp INT NOT NULL DEFAULT 0);

#calvin - applied to pilot on 9/7/2012
#boyd - applied to plano on 8/30/2012
DROP TABLE tdo_user_account_log;
CREATE TABLE tdo_user_account_log(userid VARCHAR(36) NOT NULL, owner_userid VARCHAR(36) NOT NULL, change_type TINYINT NOT NULL DEFAULT 0, description VARCHAR(512) NOT NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_account_log_userid(userid), INDEX tdo_user_account_log_owner_userid(owner_userid));
CREATE INDEX tdo_stripe_payment_history_timestamp ON tdo_stripe_payment_history(timestamp);
ALTER TABLE tdo_stripe_payment_history ADD COLUMN card_type VARCHAR(32) NOT NULL AFTER stripe_chargeid;
ALTER TABLE tdo_stripe_payment_history ADD COLUMN last4 VARCHAR(4) NOT NULL AFTER card_type;
UPDATE tdo_stripe_payment_history SET card_type='Visa',last4='4242';
ALTER TABLE tdo_list_settings ADD COLUMN hide_dashboard TINYINT;
ALTER TABLE tdo_user_settings ADD COLUMN all_list_hide_dashboard TINYINT;
ALTER TABLE tdo_user_settings ADD COLUMN starred_list_hide_dashboard TINYINT;
ALTER TABLE tdo_user_settings ADD COLUMN focus_list_hide_dashboard TINYINT;
ALTER TABLE tdo_user_accounts ADD COLUMN creation_timestamp INT NOT NULL DEFAULT 0 AFTER deactivated;
CREATE INDEX tdo_user_accounts_creation_timestamp ON tdo_user_accounts(creation_timestamp);
UPDATE tdo_user_accounts SET creation_timestamp=UNIX_TIMESTAMP();

#boyd - applied to plano and pilot on 8/29/2012
CREATE TABLE tdo_user_account_log(userid VARCHAR(36) NOT NULL PRIMARY KEY, owner_userid VARCHAR(36) NOT NULL, change_type TINYINT NOT NULL DEFAULT 0, description VARCHAR(512) NOT NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_account_log_owner_userid(owner_userid));


#boyd - applied to pilot and plano on 8/28/2012
ALTER TABLE tdo_promo_codes ADD COLUMN userid VARCHAR(36) AFTER promo_code;
CREATE INDEX tdo_promo_codes_userid ON tdo_promo_codes(userid);
CREATE TABLE tdo_promo_code_history(userid VARCHAR(36) NOT NULL, subscription_duration INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, creator_userid VARCHAR(36) NOT NULL, creation_timestamp INT NOT NULL DEFAULT 0, note VARCHAR(255), INDEX tdo_promo_code_history_userid (userid), INDEX tdo_promo_code_history_creator_userid (creator_userid));
CREATE TABLE tdo_user_payment_system(userid VARCHAR(36) NOT NULL PRIMARY KEY, payment_system_type TINYINT NOT NULL DEFAULT 0, payment_system_userid VARCHAR(36) NOT NULL);


#calvin - applied to pilot on 8/27/2012
#calvin - applied to plano on 8/27/2012
ALTER TABLE tdo_user_accounts CHANGE is_admin admin_level TINYINT(1) NOT NULL DEFAULT 0;
update tdo_user_accounts set admin_level=47 where admin_level>0;


#calvin - applied to pilot on 8/27/2012
#nicole - applied to plano on 8/24/2012
DROP TABLE tdo_fb_requests;
ALTER TABLE tdo_invitations ADD COLUMN fb_userid VARCHAR(36);
ALTER TABLE tdo_invitations ADD COLUMN fb_requestid VARCHAR(40);

#calvin - applied to pilot on 8/27/2012
#boyd - applied to planon on 8/24/2012
ALTER TABLE tdo_promo_codes ADD COLUMN creator_userid VARCHAR(36) NOT NULL AFTER timestamp;
ALTER TABLE tdo_promo_codes ADD COLUMN note VARCHAR(255) AFTER creator_userid;

#calvin - applied to pilot on 8/27/2012
#boyd - applied to plano on 8/23/2012
# Make sure to apply the OTHER changes BEFORE this one just to keep things sane
ALTER TABLE tdo_stripe_payment_history DROP COLUMN num_of_subscriptions;
ALTER TABLE tdo_stripe_payment_history ADD COLUMN type TINYINT(1) NOT NULL DEFAULT 0 AFTER stripe_chargeid;
UPDATE tdo_stripe_payment_history SET type=1 WHERE amount=199;
UPDATE tdo_stripe_payment_history SET type=2 WHERE amount=1999;


#calvin - applied to pilot on 8/27/2012
#boyd - applied to plano on 8/21/2012
CREATE TABLE tdo_autorenew_history(subscriptionid VARCHAR(36) PRIMARY KEY, renewal_attempts TINYINT NOT NULL DEFAULT 0, attempted_time INT NOT NULL DEFAULT 0, failure_reason VARCHAR(255));

#calvin - applied to pilot on 8/27/2012
#nicole - applied to plano on 8/17/12
DROP TABLE tdo_subscription_invitations;
DROP INDEX tdo_subscriptions_member_userid ON tdo_subscriptions;
ALTER TABLE tdo_subscriptions CHANGE owner_userid userid VARCHAR(36) NOT NULL;
DELETE FROM tdo_subscriptions WHERE member_userid='';
ALTER TABLE tdo_subscriptions DROP COLUMN member_userid;
CREATE UNIQUE INDEX tdo_subscriptions_userid ON tdo_subscriptions(userid);
ALTER TABLE tdo_subscriptions ADD COLUMN type TINYINT(1) NOT NULL DEFAULT 0 AFTER expiration_date;



#calvin - applied to pilot and plano 8/16/2012
ALTER TABLE tdo_stripe_user_info DROP COLUMN autorenew;


#calvin - applied to pilot and plano 8/13/2012
ALTER TABLE tdo_comments DROP COLUMN listid;
CREATE TABLE tdo_iap_payment_history(userid VARCHAR(36) NOT NULL, product_id VARCHAR(64) NOT NULL, transaction_id VARCHAR(255) NOT NULL, purchase_date VARCHAR(255) NOT NULL, app_item_id VARCHAR(255), version_external_identifier VARCHAR(255), bid VARCHAR(64), bvrs VARCHAR(36), INDEX tdo_iap_payment_history_userid (userid), INDEX tdo_iap_payment_history_transaction_id (transaction_id));



CREATE TABLE tdo_password_reset(resetid VARCHAR(36) PRIMARY KEY, userid VARCHAR(36), username VARCHAR(50), timestamp INT NOT NULL DEFAULT 0);

ALTER TABLE tdo_user_settings ADD focus_show_subtasks TINYINT(1);
ALTER TABLE tdo_user_settings ADD task_creation_email VARCHAR(50);
CREATE UNIQUE INDEX tdo_user_settings_task_creation_email on tdo_user_settings(task_creation_email);

ALTER TABLE tdo_tasks ADD project_starred INT;
UPDATE tdo_tasks SET project_starred=starred WHERE task_type=1;
UPDATE tdo_tasks SET starred=has_starred_child WHERE task_type=1 AND starred=0;
ALTER TABLE tdo_tasks DROP COLUMN has_starred_child;


ALTER TABLE tdo_tasks ADD project_duedate INT;
UPDATE tdo_tasks SET project_duedate=duedate WHERE task_type=1;
UPDATE tdo_tasks SET duedate=earliest_child_duedate WHERE task_type=1;
ALTER TABLE tdo_tasks DROP COLUMN earliest_child_duedate;

ALTER TABLE tdo_tasks ADD project_duedate_has_time TINYINT(1);
UPDATE tdo_tasks SET project_duedate_has_time=due_date_has_time WHERE task_type=1;
UPDATE tdo_tasks SET due_date_has_time=earliest_child_duedate_has_time WHERE task_type=1;
ALTER TABLE tdo_tasks DROP COLUMN earliest_child_duedate_has_time;

ALTER TABLE tdo_tasks ADD project_priority INT;
UPDATE tdo_tasks SET project_priority=priority WHERE task_type=1;
UPDATE tdo_tasks SET priority=highest_child_priority WHERE task_type=1;
ALTER TABLE tdo_tasks DROP COLUMN highest_child_priority;

ALTER TABLE tdo_tasks DROP COLUMN uncompleted_child_count;

#nicole - applied to plano on 7/16/12
#ALTER TABLE tdo_tasks ADD earliest_child_duedate_has_time TINYINT(1) NOT NULL DEFAULT 0;
#ALTER TABLE tdo_tasks ADD sort_order INT NOT NULL DEFAULT 0;
#ALTER TABLE tdo_taskitos ADD sort_order INT NOT NULL DEFAULT 0;

#nicole - applied to plano on 7/13/12
#ALTER TABLE tdo_user_settings ADD task_sort_order INT NOT NULL DEFAULT 0;

# calvin - rolled out new DB on plano (from manageDB.php) on 7/23/2012

#calvin - applied to pilot.appigo.com on 7/23/2012
CREATE INDEX tdo_user_accounts_username_index on tdo_user_accounts(username)
CREATE TABLE tdo_user_migrations(userid VARCHAR(36) PRIMARY KEY, daemonid VARCHAR(36), username VARCHAR(50), password VARCHAR(32), original_subscription_expiration_date INT NOT NULL DEFAULT 0, subscription_time_added INT NOT NULL DEFAULT 0, subscription_expiration_date INT NOT NULL DEFAULT 0, migration_completion_date INT NOT NULL DEFAULT 0, migration_last_attempt INT NOT NULL DEFAULT 0, INDEX tdo_user_migrations_username_index (username), INDEX tdo_user_migrations_completion_date_index (migration_completion_date), INDEX tdo_user_migrations_daemonid_index (daemonid))



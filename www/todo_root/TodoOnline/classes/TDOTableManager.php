<?php
include_once('TodoOnline/DBConstants.php');


class TDOTableManager
{
    public static function createDatabase()
    {
        $link = TDOUtil::getDBLink(false);
        if (!$link) 
        {
           return false;
        }

        $sql = "CREATE DATABASE ".DB_NAME;
        if (mysql_query($sql, $link)) 
        {
            TDOUtil::closeDBLink($link);
            return true;
        } 

        TDOUtil::closeDBLink($link);
        return false;

    }
    
    public static function deleteDatabase()
    {
        $link = TDOUtil::getDBLink();
        if (!$link) 
        {
            die('Could not connect: ' . mysql_error());
        }

        $sql = "DROP DATABASE ".DB_NAME;
        if (mysql_query($sql, $link))
        {
            TDOUtil::closeDBLink($link);  
            return true;
            
        } 

        TDOUtil::closeDBLink($link); 
        return false;   
    }

    public static function createUserTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_user_accounts(userid VARCHAR(36) NOT NULL, username VARCHAR(100), email_verified TINYINT(1), email_opt_out TINYINT(1), password VARCHAR(64), oauth_provider TINYINT NOT NULL DEFAULT 0, oauth_uid VARCHAR(36), first_name VARCHAR(60), last_name VARCHAR(60), admin_level TINYINT(1) NOT NULL DEFAULT 0, deactivated TINYINT(1), last_reset_timestamp INT, creation_timestamp INT NOT NULL DEFAULT 0, image_guid VARCHAR(36), image_update_timestamp INT, INDEX tdo_user_accounts_username_index (username(10)), INDEX tdo_user_accounts_creation_timestamp (creation_timestamp), INDEX tdo_user_accounts_pk(userid(10)))";
			if(mysql_query($sql, $link))
			{
                $sql = "CREATE TABLE tdo_user_settings(userid VARCHAR(36) NOT NULL, timezone VARCHAR(36), user_inbox VARCHAR(36), tag_filter_with_and TINYINT(1) NOT NULL DEFAULT 0, task_sort_order INT NOT NULL DEFAULT 0, start_date_filter INT UNSIGNED, focus_show_undue_tasks TINYINT(1) NOT NULL DEFAULT 0, focus_show_starred_tasks TINYINT(1) NOT NULL DEFAULT 0, focus_show_completed_date INT NOT NULL DEFAULT 0, focus_hide_task_date INT NOT NULL DEFAULT 2, focus_hide_task_priority INT NOT NULL DEFAULT 0, focus_list_filter_string TEXT, focus_show_subtasks TINYINT(1), focus_ignore_start_dates TINYINT(1), task_creation_email VARCHAR(50), referral_code VARCHAR(10), INDEX tdo_user_settings_pk(userid(10)), UNIQUE INDEX tdo_user_settings_task_creation_email (task_creation_email), INDEX tdo_user_settings_referral_code_idx(referral_code(10)), all_list_hide_dashboard TINYINT, starred_list_hide_dashboard TINYINT, focus_list_hide_dashboard TINYINT, all_list_filter_string TEXT, default_duedate TINYINT, show_overdue_section TINYINT(1), skip_task_date_parsing TINYINT(1), skip_task_priority_parsing TINYINT(1), skip_task_list_parsing TINYINT(1), skip_task_context_parsing TINYINT(1), skip_task_tag_parsing TINYINT(1), skip_task_checklist_parsing TINYINT(1), skip_task_project_parsing TINYINT(1), skip_task_startdate_parsing TINYINT(1), new_feature_flags BIGINT UNSIGNED, email_notification_defaults INT UNSIGNED)";
                if(mysql_query($sql, $link))
                {                
                    TDOUtil::closeDBLink($link);

                    $user = new TDOUser();
                    $user->setUsername("pigeon");
                    $user->setPassword("hotdog");
                    
                    if($user->addUser() == true)
                    {
                        error_log("Adding admin pigeon user");
						TDOUser::setAdminLevel($user->userId(), ADMIN_LEVEL_ROOT);
                    }
                    else
                        error_log("Failed to add admin pigeon user");

                    
                    return true;
                }
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
    
    
    public static function createUserMigrationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_user_migrations(userid VARCHAR(36) NOT NULL, daemonid VARCHAR(36), username VARCHAR(100), password VARCHAR(64), original_subscription_expiration_date INT NOT NULL DEFAULT 0, subscription_time_added INT NOT NULL DEFAULT 0, subscription_expiration_date INT NOT NULL DEFAULT 0, migration_completion_date INT NOT NULL DEFAULT 0, migration_last_attempt INT NOT NULL DEFAULT 0, INDEX tdo_user_migrations_username_index (username(10)), INDEX tdo_user_migrations_completion_date_index (migration_completion_date), INDEX tdo_user_migrations_daemonid_index (daemonid(10)), INDEX tdo_user_migrations_pk(userid(10)))";
			if(mysql_query($sql, $link))
			{
                TDOUtil::closeDBLink($link);
                return true;
			}
            error_log("Failed to create user user migration table with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
    
    public static function createEmailVerificationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE tdo_email_verifications(verificationid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_email_verifications_pk(verificationid(10)))";
            
            if(mysql_query($sql, $link))
            {
                TDOUtil::closeDBLink($link);
                return true;
            
            }
            error_log("Failed to create email verification table with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
        }
        return false;
    }


    public static function createSessionTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_user_sessions(sessionid VARCHAR(36) NOT NULL, userid VARCHAR(36), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_sessions_pk(sessionid(10)))";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
	
	public static function createEventTable()
	{
		$link = TDOUtil::getDBLink();
		if ($link)
		{
			$sql = "CREATE TABLE tdo_events(eventid VARCHAR(36) NOT NULL, listid VARCHAR(36), summary VARCHAR(255), description VARCHAR(512), location VARCHAR(255), startdate INT NOT NULL DEFAULT 0, enddate INT NOT NULL DEFAULT 0, hastime INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, caldavuri VARCHAR(255), caldavdata BLOB, deleted TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_events_pk(eventid(10)))";
			if (mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
			
			TDOUtil::closeDBLink($link);
		}
		else
		{
			error_log("Failed to connect with DB in PBTableManager::createEventTable()");
		}
		return false;
	}
	
	public static function createTaskTable()
	{
		$link = TDOUtil::getDBLink();
		if ($link)
		{
            /**** IMPORTANT: if you're going to modify these fields, you MUST modify the define TASK_TABLE_FIELDS and TASK_TABLE_FIELDS_STATIC in DBConstants.php to match ****/
            $taskFields = "taskid VARCHAR(36) NOT NULL, listid VARCHAR(36), name VARCHAR(510), parentid VARCHAR(36), note TEXT, startdate INT NOT NULL DEFAULT 0,  duedate INT NOT NULL DEFAULT 0, due_date_has_time TINYINT(1) NOT NULL DEFAULT 0, completiondate INT NOT NULL DEFAULT 0, priority INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, caldavuri VARCHAR(255), caldavdata BLOB, deleted TINYINT(1) NOT NULL DEFAULT 0, task_type INT NOT NULL DEFAULT 0, type_data TEXT, starred TINYINT(1) NOT NULL DEFAULT 0, assigned_userid VARCHAR(36), recurrence_type INT NOT NULL DEFAULT 0, advanced_recurrence_string VARCHAR(255), project_startdate INT, project_duedate INT, project_duedate_has_time TINYINT(1), project_priority INT, project_starred TINYINT(1), location_alert TEXT, sort_order INT NOT NULL DEFAULT 0";
        
			$sql = "CREATE TABLE tdo_tasks($taskFields, INDEX tdo_tasks_pk(taskid(10)))";
			if (mysql_query($sql, $link))
			{
                $sql = "CREATE TABLE tdo_completed_tasks($taskFields, INDEX tdo_completed_tasks_pk(taskid(10)))";
                if(mysql_query($sql, $link))
                {
                    $sql = "CREATE TABLE tdo_deleted_tasks($taskFields, INDEX tdo_deleted_tasks_pk(taskid(10)))";
                    if(mysql_query($sql, $link))
                    {
                        $sql = "CREATE TABLE tdo_archived_tasks($taskFields, INDEX tdo_archived_tasks_pk(taskid(10)))";
                        if(mysql_query($sql, $link))
                        {
                            TDOUtil::closeDBLink($link);
                            return true;
                        }
                    }
                }
			}
			
			TDOUtil::closeDBLink($link);
		}
		else
		{
			error_log("Failed to connect with DB in TDOTableManager::createTaskTable()");
		}
		return false;
	}

	public static function createTaskitoTable()
	{
		$link = TDOUtil::getDBLink();
		if ($link)
		{
			$sql = "CREATE TABLE tdo_taskitos(taskitoid VARCHAR(36) NOT NULL, parentid VARCHAR(36), name VARCHAR(510), completiondate INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, INDEX tdo_taskitos_pk(taskitoid(10)))";
			if (mysql_query($sql, $link))
			{
                $sql = "CREATE TABLE tdo_archived_taskitos(taskitoid VARCHAR(36) NOT NULL, parentid VARCHAR(36), name VARCHAR(510), completiondate INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, INDEX tdo_archived_taskitos_pk(taskitoid(10)))";
                if (mysql_query($sql, $link))
                {
                    TDOUtil::closeDBLink($link);
                    return true;
                }
			}
			
			TDOUtil::closeDBLink($link);
		}
		else
		{
			error_log("Failed to connect with DB in PBTableManager::createTaskitoTable()");
		}
		return false;
	}
    
    public static function createTaskNotificationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE tdo_task_notifications(notificationid VARCHAR(36) NOT NULL, taskid VARCHAR(36), timestamp INT NOT NULL DEFAULT 0, sound_name TEXT, deleted TINYINT(1) NOT NULL DEFAULT 0, triggerdate INTEGER NOT NULL DEFAULT 0, triggeroffset INTEGER NOT NULL DEFAULT 0, INDEX tdo_task_notifications_pk(notificationid(10)))";
            
            if (mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            else
            {
                error_log("Failed to create task notification table with error:".mysql_error());
            }
			
			TDOUtil::closeDBLink($link);
        }
        else
        {
            error_log("Failed to connect with DB in TDOTableManager::createTaskNotificationTable()");
        }
        return false;
    }
    
    public static function createListTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_lists(listid VARCHAR(36) NOT NULL, name VARCHAR(72), description TEXT, creator VARCHAR(36), cdavUri VARCHAR(255), cdavTimeZone VARCHAR(255), deleted TINYINT(1) NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, task_timestamp INT NOT NULL DEFAULT 0, notification_timestamp INT NOT NULL DEFAULT 0, taskito_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_lists_pk(listid(10)))";
			if(mysql_query($sql, $link))
			{
				$sql = "CREATE TABLE tdo_list_memberships(listid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, membership_type INT NOT NULL DEFAULT 0, INDEX tdo_list_memberships_pk(listid(10),userid(10)))";
				if(mysql_query($sql, $link))
				{
					TDOUtil::closeDBLink($link);
					return true;
				}

				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }	

    
    public static function createContextTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_contexts(contextid VARCHAR(36) NOT NULL, userid VARCHAR(36), name VARCHAR(72), deleted TINYINT(1) NOT NULL DEFAULT 0, context_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_contexts_pk(contextid(10)))";
			if(mysql_query($sql, $link))
			{
				$sql = "CREATE TABLE tdo_context_assignments(taskid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, contextid VARCHAR(36), context_assignment_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_context_assignments_pk(taskid(10), userid(10)), INDEX tdo_context_assignments_contextid_idx(contextid(10)))";
				if(mysql_query($sql, $link))
				{
					TDOUtil::closeDBLink($link);
					return true;
				}
				
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }	
	
	public static function createTagTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE tdo_tags(tagid VARCHAR(36) NOT NULL, name VARCHAR(72) COLLATE latin1_general_cs, INDEX tdo_tags_pk(tagid(10)))";
            if(mysql_query($sql, $link))
            {
                $sql = "CREATE TABLE tdo_tag_assignments(tagid VARCHAR(36) NOT NULL, taskid VARCHAR(36) NOT NULL, INDEX tdo_tag_assignments_pk(tagid(10),taskid(10)))";
                if(mysql_query($sql, $link))
                {
                    TDOUtil::closeDBLink($link);
                    return true;
                }

            
                TDOUtil::closeDBLink($link);
                return true;
            }
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
	
    public static function createListSettingsTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE tdo_list_settings(listid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, color VARCHAR(20), timestamp INT NOT NULL DEFAULT 0, cdavOrder VARCHAR(7), cdavColor VARCHAR(10), sync_filter_tasks TINYINT(1) NOT NULL DEFAULT 0, task_notifications TINYINT NOT NULL DEFAULT 0, user_notifications TINYINT NOT NULL DEFAULT 0, comment_notifications TINYINT NOT NULL DEFAULT 0, notify_assigned_only TINYINT(1), hide_dashboard TINYINT, icon_name VARCHAR(64), sort_order TINYINT DEFAULT 0, INDEX tdo_list_settings_pk(listid(10),userid(10)), INDEX tdo_list_settings_userid_idx(userid(10)))";
            if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }

    public static function createChangeLogTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_change_log(changeid VARCHAR(36) NOT NULL, listid VARCHAR(36), userid VARCHAR(36), itemid VARCHAR(36), item_name VARCHAR(72), item_type SMALLINT NOT NULL DEFAULT 0, change_type SMALLINT NOT NULL DEFAULT 0, targetid VARCHAR(36), target_type SMALLINT NOT NULL DEFAULT 0, mod_date INT NOT NULL DEFAULT 0, serializeid VARCHAR(36), deleted TINYINT(1) NOT NULL DEFAULT 0, change_location TINYINT(1) NOT NULL DEFAULT 0, change_data TEXT, INDEX tdo_change_log_pk(changeid(10)))";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
	
    public static function createEmailNotificationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_email_notifications(changeid VARCHAR(36) NOT NULL, queue_daemon_owner VARCHAR(5), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_email_notifications_pk(changeid(10)))";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;        
    }


    
    public static function createInvitationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_invitations(invitationid VARCHAR(36) NOT NULL, userid VARCHAR(36), listid VARCHAR(36), email VARCHAR(100), invited_userid VARCHAR(36), timestamp INT NOT NULL DEFAULT 0, membership_type INT NOT NULL DEFAULT 0, fb_userid VARCHAR(36), fb_requestid VARCHAR(40), INDEX tdo_invitations_pk(invitationid(10)), INDEX tdo_invited_user_index(invited_userid(10)))";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;    
    }
    
    public static function createPasswordResetTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE tdo_password_reset(resetid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_password_reset_pk(resetid(10)))";
            if(mysql_query($sql, $link))
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
    
    public static function createCommentTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE tdo_comments(commentid VARCHAR(36) NOT NULL, userid VARCHAR(36), itemid VARCHAR(36), item_type INT NOT NULL DEFAULT 0, item_name VARCHAR(72), text TEXT, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_comments_pk(commentid(10)))";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;   
    }
	
	
	//
	// Promo Code Tables
	//
	
	public static function createPromoCodesTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_promo_codes(promo_code VARCHAR(36) NOT NULL, userid VARCHAR(36), subscription_duration INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, creator_userid VARCHAR(36) NOT NULL, note VARCHAR(255), INDEX tdo_promo_codes_pk(promo_code(10)), INDEX tdo_promo_codes_userid (userid(10)))");
	}
	
	public static function createPromoCodeHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_promo_code_history(userid VARCHAR(36) NOT NULL, subscription_duration INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, creator_userid VARCHAR(36) NOT NULL, creation_timestamp INT NOT NULL DEFAULT 0, note VARCHAR(255), INDEX tdo_promo_code_history_userid (userid), INDEX tdo_promo_code_history_creator_userid (creator_userid))");
	}
	
    //
    // Gift Code Tables
    //
    
    public static function createGiftCodesTable()
    {
        return TDOTableManager::createGenericTable("CREATE TABLE tdo_gift_codes (gift_code VARCHAR(36), subscription_duration INT NOT NULL DEFAULT 0, stripe_gift_payment_id VARCHAR(36), purchaser_userid VARCHAR(36), purchase_timestamp INT NOT NULL DEFAULT 0, sender_name VARCHAR(100), recipient_name VARCHAR(100), recipient_email VARCHAR(100), consumption_date INT NOT NULL DEFAULT 0, consumer_userid VARCHAR(36), message VARCHAR(255), INDEX tdo_gift_codes_pk(gift_code(10)), INDEX tdo_gift_codes_purchaser_userid(purchaser_userid(10)), INDEX tdo_gift_codes_stripe_payment_id(stripe_gift_payment_id(10)) )");
    }
    
    public static function createStripeGiftPaymentHistoryTable()
    {
        return TDOTableManager::createGenericTable("CREATE TABLE tdo_stripe_gift_payment_history (stripe_gift_payment_id VARCHAR(36), userid VARCHAR(36), stripe_userid VARCHAR(255) NOT NULL, stripe_chargeid VARCHAR(255) NOT NULL, card_type VARCHAR(32) NOT NULL, last4 VARCHAR(4) NOT NULL, amount DECIMAL NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_stripe_gift_payment_history_pk(stripe_gift_payment_id(10)), INDEX tdo_stripe_gift_payment_history_userid(userid(10)) )");

    }
	
	//
	// Subscription Tables
	//
	
	public static function createSubscriptionsTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_subscriptions(subscriptionid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, expiration_date INT NOT NULL DEFAULT 0, type TINYINT(1) NOT NULL DEFAULT 0, level TINYINT(1) NOT NULL DEFAULT 0, teamid VARCHAR(36) NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_subscriptions_pk(subscriptionid(10)), INDEX tdo_subscriptions_userid(userid(10)), INDEX tdo_subscriptions_teamid(teamid(10)), INDEX tdo_subscriptions_expiration_date_idx(expiration_date))");
	}
	
	public static function createUserPaymentSystemTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_user_payment_system(userid VARCHAR(36) NOT NULL, payment_system_type TINYINT NOT NULL DEFAULT 0, payment_system_userid VARCHAR(36) NOT NULL, INDEX tdo_user_payment_system_pk(userid(10)))");
	}
	
	public static function createStripeUserInfoTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_stripe_user_info(userid VARCHAR(36) NOT NULL, stripe_userid VARCHAR(255) NOT NULL, INDEX tdo_stripe_user_info_pk(userid(10)))");
	}
	
	public static function createStripePaymentHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_stripe_payment_history(userid VARCHAR(36) NOT NULL, teamid VARCHAR(255) NULL, license_count INT NOT NULL DEFAULT 0, stripe_userid VARCHAR(255) NOT NULL, stripe_chargeid VARCHAR(255) NOT NULL, card_type VARCHAR(32) NOT NULL, last4 VARCHAR(4) NOT NULL, type TINYINT(1) NOT NULL DEFAULT 0, amount INT NOT NULL DEFAULT 0, charge_description VARCHAR(128), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_stripe_payment_history_userid (userid), INDEX tdo_stripe_payment_history_teamid (teamid(10)), INDEX tdo_stripe_payment_history_stripe_userid (stripe_userid), INDEX tdo_stripe_payment_history_timestamp (timestamp))");
	}
	
	public static function createIAPPaymentHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_iap_payment_history(userid VARCHAR(36) NOT NULL, product_id VARCHAR(64) NOT NULL, transaction_id VARCHAR(255) NOT NULL, purchase_date VARCHAR(255) NOT NULL, app_item_id VARCHAR(255), version_external_identifier VARCHAR(255), bid VARCHAR(64), bvrs VARCHAR(36), INDEX tdo_iap_payment_history_userid (userid), INDEX tdo_iap_payment_history_transaction_id (transaction_id))");
	}
    
    //This stores the latest IAP autorenew receipt for the user, which we can use to check for further renewals when the user's account is about to expire
    //Once we detect that the user has canceled their autorenewal, we'll set the autorenewal_canceled flag
    //We should record all renewals in the iap_payment_history table as we process them, so the transaction_id field in this table should correspond to a transaction_id in that table
    public static function createIAPAutorenewReceiptTable()
    {
        return TDOTableManager::createGenericTable("CREATE TABLE tdo_iap_autorenew_receipts (userid VARCHAR(36) NOT NULL, latest_receipt_data BLOB NOT NULL, expiration_date INT NOT NULL DEFAULT 0, transaction_id VARCHAR(255) NOT NULL, autorenewal_canceled TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_iap_autorenew_receipts_userid (userid), INDEX tdo_iap_autorenewal_canceled (autorenewal_canceled))");
    }
	
	public static function createAutorenewHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_autorenew_history(subscriptionid VARCHAR(36) NOT NULL, renewal_attempts TINYINT NOT NULL DEFAULT 0, attempted_time INT NOT NULL DEFAULT 0, failure_reason VARCHAR(255), INDEX tdo_autorenew_history_pk(subscriptionid(10)))");
	}
	
	
	//
	// User Account Log
	//
	public static function createUserAccountLog()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_user_account_log(userid VARCHAR(36) NOT NULL, owner_userid VARCHAR(36) NOT NULL, change_type TINYINT NOT NULL DEFAULT 0, description VARCHAR(512) NOT NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_account_log_userid(userid(10)), INDEX tdo_user_account_log_owner_userid(owner_userid(10)))");
	}

	
	//
	// Device Table
	//
	public static function createUserDeviceTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_user_devices(deviceid VARCHAR(36) NOT NULL, user_deviceid VARCHAR(80) NOT NULL, userid VARCHAR(36) NOT NULL, sessionid VARCHAR(36) NOT NULL, devicetype VARCHAR(36) NOT NULL, osversion VARCHAR(36) NOT NULL, appid VARCHAR(80) NOT NULL, appversion VARCHAR(36) NOT NULL, timestamp INT NOT NULL DEFAULT 0, error_number INT NOT NULL DEFAULT 0, error_message TEXT,  INDEX tdo_user_devices_deviceid(user_deviceid(10)), INDEX tdo_user_devices_userid(userid(10)), INDEX tdo_user_devices_sessionid(sessionid(10)))");
	}
	
	//
	// Bounced Emails Table
	//
	public static function createBouncedEmailsTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_bounced_emails(email VARCHAR(100) NOT NULL, bounce_type INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, bounce_count INT NOT NULL DEFAULT 0, INDEX tdo_bounced_emails_email(email(10)))");
	}
	
	//
	// User Maintenance Table
	//
	public static function createUserMaintenanceTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_user_maintenance(userid VARCHAR(100) NOT NULL, operation_type INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, daemonid VARCHAR(36), INDEX tdo_user_maintenance_userid_idx(userid(10)), INDEX tdo_user_maintenance_op_type_idx(operation_type), INDEX tdo_user_maintenance_daemonid_idx(daemonid(10)))");
	}
    
    //
    //System Notification Table
    //
    
    public static function createSystemNotificationTable()
    {
        return TDOTableManager::createGenericTable("CREATE TABLE tdo_system_notifications(notificationid VARCHAR(36) NOT NULL, message TEXT, timestamp INT NOT NULL DEFAULT 0, deleted TINYINT(1) NOT NULL DEFAULT 0, learn_more_url TEXT, INDEX tdo_system_notifications_pk(notificationid(10)))");
    }
	
	//
	// Referrals Table
	//
	
	public static function createReferralsTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_user_referrals (consumer_userid VARCHAR(36) NOT NULL, referral_code VARCHAR(10) NOT NULL, purchase_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_referrals_consumer(consumer_userid(10)), INDEX tdo_user_referrals_code(referral_code(10)))");
	}
	
	public static function createReferralUnsubscribersTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_referral_unsubscribers (email VARCHAR(100) NOT NULL, INDEX tdo_referral_unsubscriber_idx(email(10)))");
	}
	
	public static function createReferralCreditHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_referral_credit_history (userid VARCHAR(36) NOT NULL, consumer_userid VARCHAR(36) NOT NULL, extension_days INT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_referral_credit_history_userid (userid(10)))");
	}
	
	//
	// Google Play Subscription Purchases Tables
	//
	
	public static function createGooglePlayPaymentHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_googleplay_payment_history(userid VARCHAR(36) NOT NULL, product_id VARCHAR(128) NOT NULL, purchase_timestamp INT NOT NULL DEFAULT 0, expiration_timestamp INT NOT NULL DEFAULT 0, INDEX tdo_googleplay_payment_history_userid (userid))");
	}
	
	public static function createGooglePlayAutorenewTokensTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_googleplay_autorenew_tokens(userid VARCHAR(36) NOT NULL, product_id VARCHAR(128) NOT NULL, token VARCHAR(512) NOT NULL, expiration_date INT NOT NULL DEFAULT 0, autorenewal_canceled TINYINT(1) NOT NULL DEFAULT 0, INDEX tdo_googleplay_autorenew_tokens_userid (userid), INDEX tdo_googleplay_autorenew_tokens_canceled (autorenewal_canceled))");
	}
	
	//
	// System Settings Table
	//
	
	public static function createSystemSettingsTable()
	{
		if (!TDOTableManager::createGenericTable("CREATE TABLE tdo_system_settings(setting_id VARCHAR(255) NOT NULL, setting_value VARCHAR(512) NOT NULL, INDEX tdo_system_settings_setting_id (setting_id))"))
		{
			return false;
		}
		
		// Add in our settings for Google Play
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_REFRESH_TOKEN', '1/SySK-zrFna4R4_GL7pgKrb4P8fmLh5Bz8mEj5SH9xnE')";
			if (!mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			$sql = "INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_CLIENT_ID', '443806992058-5f6l7a31ntd7qr8hbr1mkfl113qstbiq.apps.googleusercontent.com')";
			if (!mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			$sql = "INSERT INTO tdo_system_settings(setting_id, setting_value) VALUES ('GOOGLE_PLAY_CLIENT_SECRET', 'sDhFZTilJbaqEPKYyhMpKqRX')";
			if (!mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return false;
			}
			
            TDOUtil::closeDBLink($link);
			return true;
        }
        return false;
	}
	
	
	///
	/// Team Accounts
	///
	public static function createTeamAccountsTable()
	{
		/*
		 
		 tdo_team_accounts
			teamid				varchar(36)		KEY
			teamname			varchar(128)
			license_count		int(11)
			billing_userid		varchar(36)		This links to a userid that MUST be an
												admin and also have a valid credit card
												on file for autorenewal to work. If a
												different admin changes billing info,
												the last admin to change this “wins”.
												Initially, this will be the creator of
												the team account.
			license_expiration_date		int(11)
			creation_date				int(11)
			modified_date				int(11)
			billing_frequency			tinyint(1)	0 for monthly, 1 for yearly
			new_license_count			int(11)		Autorenewal Downgrade
				If new_member_count > 0
				member_count = new_member_count;
				new_member_count = 0;
				Then, it processes renewal accordingly.
		 
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_name VARCHAR(128) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_phone VARCHAR(32) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_addr1 VARCHAR(128) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_addr2 VARCHAR(128) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_city VARCHAR(64) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_state VARCHAR(64) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_country VARCHAR(64) NULL;
		 ALTER TABLE tdo_team_accounts ADD COLUMN biz_postal_code VARCHAR(32) NULL;
		 */
		
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_accounts(teamid VARCHAR(36) NOT NULL, teamname VARCHAR(128) NOT NULL, license_count INT NOT NULL DEFAULT 0, billing_userid VARCHAR(36) NULL, expiration_date INT NOT NULL DEFAULT 0, creation_date INT NOT NULL DEFAULT 0, modified_date INT NOT NULL DEFAULT 0, billing_frequency TINYINT(1) NOT NULL DEFAULT 0, new_license_count INT NOT NULL DEFAULT 0, biz_name VARCHAR(128) NULL, biz_phone VARCHAR(32) NULL, biz_addr1 VARCHAR(128) NULL, biz_addr2 VARCHAR(128) NULL, biz_city VARCHAR(64) NULL, biz_state VARCHAR(64) NULL, biz_country VARCHAR(64) NULL, biz_postal_code VARCHAR(32) NULL, INDEX tdo_team_accounts_teamid (teamid(10)), INDEX tdo_team_accounts_license_count (license_count), INDEX tdo_team_accounts_billing_userid (billing_userid(10))");
	}
	public static function createTeamAdminsTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_admins(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, INDEX tdo_team_admins_pk(teamid(10)), INDEX tdo_team_admins_fullkey(teamid(10),userid(10)), INDEX tdo_team_admins_userid(userid(10)))");
	}
	public static function createTeamMembersTable()
	{
		/*
		 tdo_team_members
			teamid				varchar(36)        // PRIMARY KEY
			userid				varchar(36)        // KEY
		 */
		
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_members(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, INDEX tdo_team_members_pk(teamid(10)), INDEX tdo_team_members_fullkey(teamid(10),userid(10)), INDEX tdo_team_members_userid(userid(10)))");
	}
	public static function createTeamInvitationsTable()
	{
		/*
		 
		 tdo_team_invitations
			 invitationid		varchar(36)
			 userid				varchar(36)        // who the invitation is from
			 teamid				varchar(36)
			 email				varchar(100)
			 invited_userid		varchar(36)
			 timestamp			int(11)
			 membership_type	int(11)
			 fb_userid			varchar(36)        // Not sure if this is needed
			 rb_requestid		varchar(40)        // Not sure if this is needed
		 
		 */
		
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_invitations(invitationid VARCHAR(36) NOT NULL, userid VARCHAR(36), teamid VARCHAR(36), email VARCHAR(100), invited_userid VARCHAR(36), timestamp INT NOT NULL DEFAULT 0, membership_type INT NOT NULL DEFAULT 0, fb_userid VARCHAR(36), fb_requestid VARCHAR(40), INDEX tdo_team_invitations_pk(invitationid(10)), INDEX tdo_team_invited_user_index(invited_userid(10)), INDEX tdo_team_invited_teamid_index(teamid(10)), INDEX tdo_team_invited_teamid_email_index(teamid(10),email(10)))");
	}
	public static function createTeamAutorenewHistoryTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_autorenew_history(teamid VARCHAR(36) NOT NULL, renewal_attempts TINYINT NOT NULL DEFAULT 0, attempted_time INT NOT NULL DEFAULT 0, failure_reason VARCHAR(255), INDEX tdo_team_autorenew_history_pk(teamid(10)))");
	}
//	public static function createSalesTaxTable()
//	{
//		return TDOTableManager::createGenericTable("CREATE TABLE tdo_sales_tax(zipcode VARCHAR(5) NOT NULL, taxrate DECIMAL(5,4) DEFAULT 0, cityname VARCHAR(32) DEFAULT NULL, INDEX tdo_sales_tax_zip(zipcode))");
//	}
	public static function createTeamSubscriptionCreditsTable()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE tdo_team_subscription_credits(teamid VARCHAR(36) NOT NULL, userid VARCHAR(36) NOT NULL, donation_date INT NOT NULL DEFAULT 0, donation_months_count TINYINT NOT NULL DEFAULT 0, consumed_date INT DEFAULT NULL, refunded_date INT DEFAULT NULL, INDEX tdo_team_subscription_credits_teamid(teamid(10)), INDEX tdo_team_subscription_credits_userid(userid(10)), INDEX tdo_team_subscription_credits_donation_date(donation_date))");
	}

	
    
	//
	// Generic table creation method
	//
	
    
    public static function createAllTableIndexes()
    {
        $result = true;
        
        // tdo_lists
//        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_list_deleted_index on tdo_lists(deleted)") == false)
//            $result = false;
        
        // tdo_list_memberships
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_list_membership_userid_index on tdo_list_memberships(userid(10))") == false)
            $result = false;

        // tdo_user_sessions
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_user_sessions_userid_index on tdo_user_sessions(userid(10))") == false)
            $result = false;

        
        // tdo_tasks
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_tasks_listid_index on tdo_tasks(listid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_tasks_parentid_index on tdo_tasks(parentid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_tasks_duedate_index on tdo_tasks(duedate)") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_tasks_timestamp_index on tdo_tasks(timestamp)") == false)
            $result = false;

        
        //tdo_completed_tasks
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_completed_tasks_listid_index on tdo_completed_tasks(listid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_completed_tasks_parentid_index on tdo_completed_tasks(parentid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_completed_tasks_completiondate_index on tdo_completed_tasks(completiondate)") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_completed_tasks_timestamp_index on tdo_completed_tasks(timestamp)") == false)
            $result = false;

        
        //tdo_deleted_tasks
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_deleted_tasks_listid_index on tdo_deleted_tasks(listid(10))") == false)
            $result = false;
		
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_deleted_tasks_parentid_index on tdo_deleted_tasks(parentid(10))") == false)
            $result = false;

        
        //tdo_archived_tasks
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_archived_tasks_listid_index on tdo_archived_tasks(listid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_archived_tasks_timestamp_index on tdo_archived_tasks(timestamp)") == false)
            $result = false;

        
        // tdo_taskitos
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_taskitos_parentid_index on tdo_taskitos(parentid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_taskitos_timestamp_index on tdo_taskitos(timestamp)") == false)
            $result = false;

        // tdo_archived_taskitos
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_archived_taskitos_parentid_index on tdo_archived_taskitos(parentid(10))") == false)
            $result = false;
        
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_archived_taskitos_timestamp_index on tdo_archived_taskitos(timestamp)") == false)
            $result = false;
        
        // tdo_tag_assignments
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_tag_assignments_taskid_index on tdo_tag_assignments(taskid(10))") == false)
            $result = false;
        
        // tdo_contexts
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_contexts_context_timestamp_index on tdo_contexts(context_timestamp)") == false)
            $result = false;

        // tdo_context_assignments
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_context_assignments_userid_index on tdo_context_assignments(userid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_context_assignments_taskid_index on tdo_context_assignments(taskid(10))") == false)
            $result = false;

        // tdo_comments
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_comments_itemid_index on tdo_comments(itemid(10))") == false)
            $result = false;
        
        // tdo_task_notifications
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_task_notifications_taskid_index on tdo_task_notifications(taskid(10))") == false)
            $result = false;

        // tdo_change_log
        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_change_log_listid_index on tdo_change_log(listid(10))") == false)
            $result = false;

        if(TDOTableManager::createGenericIndex("CREATE INDEX tdo_change_log_itemid_index on tdo_change_log(itemid(10))") == false)
            $result = false;
        
        return $result;
    }
    
    
    
	//
	// Appigo Account table methods
	//
    
    
    public static function createAppigoUserTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "CREATE TABLE appigo_user_accounts(userid VARCHAR(36) NOT NULL, username VARCHAR(100), email_verified TINYINT(1), email_opt_out TINYINT(1), password VARCHAR(64), oauth_provider TINYINT NOT NULL DEFAULT 0, oauth_uid VARCHAR(36), first_name VARCHAR(60), last_name VARCHAR(60), admin_level TINYINT(1) NOT NULL DEFAULT 0, deactivated TINYINT(1), last_reset_timestamp INT, creation_timestamp INT NOT NULL DEFAULT 0, image_guid VARCHAR(36), image_update_timestamp INT, INDEX tdo_user_accounts_username_index (username(10)), INDEX tdo_user_accounts_creation_timestamp (creation_timestamp), INDEX tdo_user_accounts_pk(userid(10)))";
			if(mysql_query($sql, $link))
			{
                TDOUtil::closeDBLink($link);
                
                return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
    
    public static function createAppigoEmailVerificationTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE appigo_email_verifications(verificationid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_email_verifications_pk(verificationid(10)), INDEX tdo_email_verifications_userid_idx(userid(10)))";
            
            if(mysql_query($sql, $link))
            {
                TDOUtil::closeDBLink($link);
                return true;
                
            }
            error_log("Failed to create email verification table with error: ".mysql_error());
            TDOUtil::closeDBLink($link);
        }
        return false;
    }
    
    public static function createAppigoPasswordResetTable()
    {
        $link = TDOUtil::getDBLink();
        if($link)
        {
            $sql = "CREATE TABLE appigo_password_reset(resetid VARCHAR(36) NOT NULL, userid VARCHAR(36), username VARCHAR(100), timestamp INT NOT NULL DEFAULT 0, INDEX tdo_password_reset_pk(resetid(10)))";
            if(mysql_query($sql, $link))
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            TDOUtil::closeDBLink($link);
        }
        return false;
    }  
    
	public static function createAppigoUserAccountLog()
	{
		return TDOTableManager::createGenericTable("CREATE TABLE appigo_user_account_log(userid VARCHAR(36) NOT NULL, owner_userid VARCHAR(36) NOT NULL, change_type TINYINT NOT NULL DEFAULT 0, description VARCHAR(512) NOT NULL, timestamp INT NOT NULL DEFAULT 0, INDEX tdo_user_account_log_userid(userid(10)), INDEX tdo_user_account_log_owner_userid(owner_userid(10)))");
	}

    
	public static function createAppigoEmailListUserTable()
	{
        return TDOTableManager::createGenericTable("CREATE TABLE appigo_email_list_user(email VARCHAR(100) NOT NULL, last_source TINYINT NOT NULL DEFAULT 0, timestamp INT NOT NULL DEFAULT 0, INDEX appigo_email_idx(email(15)))");
	}
    
    
	public static function createGenericTable($sql)
	{
        $link = TDOUtil::getDBLink();
        if($link)
        {
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;   
	}
    
    public static function deleteTable($tableName)
    {
        if(!$tableName)
            return false;
        
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "DROP TABLE $tableName";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;    
    }
    
    
	public static function createGenericIndex($sql)
	{
        $link = TDOUtil::getDBLink();
        if($link)
        {
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            TDOUtil::closeDBLink($link);
        }
        return false;
	}
    

    public static function deleteIndex($indexName, $tableName)
    {
        if(!$indexName)
            return false;
        
        $link = TDOUtil::getDBLink();
        if($link)
        {
			$sql = "DROP INDEX $indexName on $tableName";
			if(mysql_query($sql, $link))
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
            error_log("Error deleting index ".mysql_error());
            TDOUtil::closeDBLink($link);
        }
        return false;    
    }
    
    
}


?>

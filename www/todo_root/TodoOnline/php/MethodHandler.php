<?php


	$method = NULL;

	if(isset($_POST["method"]))
		$method = $_POST["method"];
	elseif(isset($_GET["method"]))
		$method = $_GET["method"];
	else
	{
		echo '{"success":false, "error":"Missing method parameter"}';
		exit();
	}

	// methods not requiring login
	switch($method)
	{
		case "login":
			include_once('TodoOnline/methodhandlers/HandleLogin.php');
			exit();
			break;
		case "jwtLogin":
			include_once('TodoOnline/methodhandlers/HandleJWTLogin.php');
			exit();
			break;
        case "logout":
            include_once('TodoOnline/methodhandlers/HandleLogout.php');
            exit();
            break;
		case "createUser":
			include_once('TodoOnline/methodhandlers/HandleCreateUser.php');
			exit();
			break;
		case "createJwtUser":
			include_once('TodoOnline/methodhandlers/HandleCreateJwtUser.php');
			exit();
			break;
		case "createTaskFromEmail":
			include_once('TodoOnline/methodhandlers/HandleEmailTaskMethods.php');
			exit();
			break;
        case "createCommentFromEmail":
            include_once('TodoOnline/methodhandlers/HandleEmailTaskReplyMethods.php');
            exit();
            break;
        case "sendResetPasswordEmail":
        case "resetPassword":
            include_once('TodoOnline/methodhandlers/HandleResetPasswordMethods.php');
            exit();
            break;
		case "processIAPSubscriptionPurchase":
			include_once('TodoOnline/methodhandlers/HandleSubscriptionMethods.php');
			exit();
			break;
		case "recordBounceEmail":
			include_once('TodoOnline/methodhandlers/HandleEmailBounceNotificationMethods.php');
			exit();
			break;
        case "slackListener":
            include_once('TodoOnline/methodhandlers/HandleTeamMethods.php');
            exit();
            break;
	}

	// check authorization
	if(!$session->isLoggedIn())
	{
		error_log("Method was called ".$method." for user that is not authenticated");

		echo '{"success":false, "error":"authentication"}';
		exit();
	}

	// all methods which require authentication
	switch($method)
	{
        case "changeRole":
        case "getMembersAndRoles":
        case "getMembers":
        case "getInvitationsForList":
            include_once('TodoOnline/methodhandlers/HandleListMemberMethods.php');
            exit();
            break;
        case "removeComment":
        case "postComment":
		case "getCommentsForObject":
            include_once('TodoOnline/methodhandlers/HandleCommentActions.php');
            exit();
            break;
        case "addList":
        case "removeListImage":
        case "updateList":
            include_once('TodoOnline/methodhandlers/HandleListMethods.php');
            exit();
            break;
        case "deleteList":
        case "getDeleteListInfo":
            include_once('TodoOnline/methodhandlers/HandleDeleteListMethods.php');
            exit();
            break;
        case "emailInvites":
        case "createFBInvites":
            include_once('TodoOnline/methodhandlers/HandleCreateInvites.php');
            exit();
            break;
        case "acceptInvite":
        case "deleteInvite":
        case "modifyFBInvite":
        case "resendInvite":
        case "updateInvite":
            include_once('TodoOnline/methodhandlers/HandleInvitationMethods.php');
            exit();
            break;
        case "getPendingFBRequestsForUser":
            include_once('TodoOnline/methodhandlers/HandleGetFBRequests.php');
            exit();
            break;
        case "setUserTimezone":
        case "setUserLanguage":
        case "changeTagFilterSetting":
        case "updateUser":
        case "updateUserMessage":
		case "generateNewTaskCreationEmail":
		case "deleteTaskCreationEmail":
        case "sendVerificationEmail":
        case "wipeUserData":
        case "reMigrateUserData":
        case "verifyUserPassword":
            include_once('TodoOnline/methodhandlers/HandleUpdateUser.php');
            exit();
            break;
        case "updateUserSettings":
        case "applyDefaultNotificationSettingsToAllLists":
            include_once('TodoOnline/methodhandlers/HandleUserSettingsMethods.php');
            exit();
        case "unlinkFacebook":
        case "linkFacebook":
            include_once('TodoOnline/methodhandlers/HandleLinkFacebook.php');
            exit();
            break;
        case "sendFeedback":
            include_once('TodoOnline/methodhandlers/HandleSendFeedback.php');
            exit();
            break;
		case "getSectionTasks":
            include_once('TodoOnline/methodhandlers/HandleGetSectionTasks.php');
            exit();
            break;
        case "getCompletedTasks":
        case "getCompletedTasksAPI":
				case "getCompletedSubtasksAPI":
				case "getCompletedTaskitosAPI":
            include_once('TodoOnline/methodhandlers/HandleGetCompletedTasks.php');
            exit();
            break;
		case "getSubtasks":
            include_once('TodoOnline/methodhandlers/HandleGetSubtasks.php');
            exit();
            break;
        case "getSearchTasks":
            include_once('TodoOnline/methodhandlers/HandleGetSearchTasks.php');
            exit();
            break;
        case "getPagedTasks":
            include_once('TodoOnline/methodhandlers/HandleGetPagedTasks.php');
            exit();
            break;
        case "getPagedChangeLogForList":
        case "getPagedChangeLogForUser":
        case "getChangeLogForChangeId":
            include_once('TodoOnline/methodhandlers/HandleGetPagedChangeLog.php');
            exit();
            break;
        case "getDashboardContent":
        case "getMoreComments":
        case "getMoreDashboardChanges":
        case "setDashboardHidden":
            include_once('TodoOnline/methodhandlers/HandleDashboardMethods.php');
            exit();
            break;
        case "addTask":
        case "moveTaskToParent":
        case "deleteTask":
        case "updateTask":
        case "groupUpdateTask":
		case "taskConvert":
        case "completeTask":
        case "changeTaskList":
        case "getTaskForTaskId":
            include_once('TodoOnline/methodhandlers/HandleTaskMethods.php');
            exit();
            break;
        case "uploadProfileImage":
        case "saveUploadedProfileImage":
        case "removeProfileImage":
            include_once('TodoOnline/methodhandlers/HandleUploadProfileImage.php');
            exit();
            break;
        case "addTaskito":
        case "deleteTaskito":
        case "moveTaskitoToParent":
        case "moveTaskitoFromParent":
        case "updateTaskito":
        case "completeTaskito":
        case "updateTaskitoSortOrders":
            include_once('TodoOnline/methodhandlers/HandleTaskitoMethods.php');
            exit();
            break;
		case "addContext":
		case "deleteContext":
		case "updateContext":
		case "assignContext":
            include_once('TodoOnline/methodhandlers/HandleContextMethods.php');
            exit();
            break;
        case "addTag":
        case "removeTagFromTask":
        case "renameTag":
        case "deleteTag":
        case "updateTagsForTask":
            include_once('TodoOnline/methodhandlers/HandleTagMethods.php');
            exit();
            break;
        case "updateListSettings":
        case "getListSettings":
        case "changeNotificationSettings":
            include_once('TodoOnline/methodhandlers/HandleListSettingsMethods.php');
            exit();
            break;
        case "uploadListImage":
            include_once('TodoOnline/methodhandlers/HandleImageUpload.php');
            exit();
            break;
        case "getControlContent":
        	include_once('TodoOnline/methodhandlers/HandleControlContent.php');
        	exit();
        	break;
        case "addTaskNotification":
        case "deleteTaskNotification":
        case "updateTaskNotification":
        case "getNotificationsForTask":
        case "getNextNotificationForUser":
            include_once('TodoOnline/methodhandlers/HandleTaskNotificationMethods.php');
            exit();
            break;
        case "purchaseGiftCodes":
        case "getGiftCodesForCurrentUser":
        case "getBillingInfoForCurrentUser":
        case "applyGiftCodeToAccount":
        case "resendGiftCodeEmail":
            include_once('TodoOnline/methodhandlers/HandleGiftCodeMethods.php');
            exit();
            break;

		//
		// Subscription Method Handlers
		//
		case "getSubscriptionInfo":
		case "purchasePremiumAccount":
        case "switchBillingMethodsFromIAP":
		case "switchAccountToMonthly":
		case "updatePaymentCardInfo":
		case "downgradeToFreeAccount":
		case "getPurchaseHistory":
		case "resendPurchaseReceipt":
		case "generateVIPPromo":
			include_once('TodoOnline/methodhandlers/HandleSubscriptionMethods.php');
			exit();
			break;

		// settings content
		case "getSettingsValues":
			include_once('TodoOnline/methodhandlers/HandleSubscriptionSettingsContent.php');
			exit();
			break;

		//
		// Referral Link Method Handlers
		//
		case "sendReferralEmail":
			include_once('TodoOnline/methodhandlers/HandleReferralLinkMethods.php');
			exit();
			break;

        //Message Center
//        case "getRecentMessages":
//        case "getUpdatedUnreadMessageCount":
//        case "updateMessages":
//            include_once('TodoOnline/methodhandlers/HandleMessageCenterMethods.php');
//            exit();
//            break;

		//
		// Teaming Method Handlers
		//
		case "getTeamPricingInfo":
		case "purchaseTeamAccount":
		case "updateTeamName":
		case "updateTeamInfo":
		case "inviteTeamMember":
		case "convertAccountToGiftCode":
		case "acceptTeamInvitation":
		case "deleteTeamInvitation":
		case "resendTeamInvitation":
		case "removeTeamMember":
		case "addMyselfToTheTeam":
		case "getTeamChangePricingInfo":
		case "changeTeamAccount":
		case "getTeamPurchaseHistory":
		case "leaveTeam":
		case "cancelTeamRenewal":
		case "updateTeamBillingInfo":
		case "deleteTeamAccount":
		case "createTeamAccountWithTrial":
		case "createSharedList":
		case "addMembersToSharedList":
		case "removeMemberFromSharedList":
		case "changeMemberRole":
		case "getActiveTeamCredits":
		case "updateSlackConfig":
		case "slackListener":
			include_once('TodoOnline/methodhandlers/HandleTeamMethods.php');
			exit();
			break;

		default:
            echo '{"success":false, "error":"Invalid method was passed"}';
			exit();
	}

?>

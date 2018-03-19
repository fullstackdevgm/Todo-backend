<?php

// NOTE: The V2 Sync Mechanism does not require a user to have a paid
// subscription in order to successfully synchronize.

	$method = NULL;

	if (isset($_POST["method"])) {
		$method = $_POST["method"];
	} elseif (isset($_GET["method"])) {
		$method = $_GET["method"];
	} else {
    outputSyncError(ERROR_CODE_MISSING_REQUIRED_PARAMETERS, ERROR_DESC_MISSING_REQUIRED_PARAMETERS);
    error_log("HandleSyncAuthentication:getSessionToken: missing parameters");
		exit();
	}

	// methods not requiring login
	switch($method)
	{
		case "getSessionToken":
    case "createFacebookUser":
			include_once('TodoOnline/syncmethodhandlers/HandleSyncAuthentication.php');
			exit();
			break;
		case "createUser":
			include_once('TodoOnline/syncmethodhandlers/HandleSyncAuthentication.php');
			exit();
			break;
    case "sendResetPasswordEmail":
      include_once('TodoOnline/methodhandlers/HandleResetPasswordMethods.php');
      exit();
      break;
	}

	// check authorization
	if(!$session->isLoggedIn())
	{
    outputSyncError(ERROR_CODE_USER_NOT_AUTHENTICATED, ERROR_DESC_USER_NOT_AUTHENTICATED);
		exit();
	}

  // getSyncInformation needs to be authenticated but it needs to not check
  // for a subscription.
  // processIAPAutorenewSubscriptionPurchase also needs to be authenticated but
  // not check for a subscription
	switch($method)
	{
    case "getSyncInformation":
			include_once('TodoOnline/syncmethodhandlers/HandleSyncAuthentication.php');
			exit();
			break;
    case "checkIAPAvailability":
    case "processIAPAutorenewSubscriptionPurchase":
		case "processGooglePlayAutorenewSubscriptionPurchase":
      include_once('TodoOnline/syncmethodhandlers/HandleInAppPurchaseMethods.php');
      exit();
      break;

    case "getUserInvites":
    case "acceptInvite":
    case "deleteInvite":
			include_once('TodoOnline/methodhandlers/HandleInvitationMethods.php');
			exit();
			break;

		case "sendVerificationEmail":
			include_once('TodoOnline/methodhandlers/HandleUpdateUser.php');
			exit();
			break;

		// The following methods are now available in V2 of the sync and no longer
		// require a paid account:

		case "getSyncInformation":
		case "updateEmailOptout":
			include_once('TodoOnline/syncmethodhandlers/HandleSyncAuthentication.php');
			exit();
			break;
		case "getSmartLists":
		case "changeSmartLists":
			include_once('TodoOnline/syncmethodhandlers/HandleSmartListSyncMethods.php');
			exit();
			break;
		case "getLists":
		case "changeLists":
			include_once('TodoOnline/syncmethodhandlers/HandleListSyncMethods.php');
			exit();
			break;
		case "syncTasks":
			include_once('TodoOnline/syncmethodhandlers/HandleTaskSyncMethods.php');
			exit();
			break;
    case "syncContexts":
			include_once('TodoOnline/syncmethodhandlers/HandleContextSyncMethods.php');
			exit();
			break;
	  case "syncNotifications":
			include_once('TodoOnline/syncmethodhandlers/TaskNotificationSyncMethods.php');
			exit();
			break;
	  case "syncTaskitos":
			include_once('TodoOnline/syncmethodhandlers/TaskitoSyncMethods.php');
			exit();
			break;
	  case "getUsers":
			include_once('TodoOnline/syncmethodhandlers/UserSyncMethods.php');
			exit();
			break;
	  case "removeComment":
	  case "postComment":
		case "getCommentsForObject":
	    include_once('TodoOnline/methodhandlers/HandleCommentActions.php');
	    exit();
	    break;
	  case "changeRole":
	  case "getMembersAndRoles":
	  case "getMembers":
	  case "getInvitationsForList":
	    include_once('TodoOnline/methodhandlers/HandleListMemberMethods.php');
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
		case "getDeleteListInfo":
	  case "deleteList":
	    include_once('TodoOnline/methodhandlers/HandleDeleteListMethods.php');
	    exit();
	    break;
		case "updateListSettings":
			include_once('TodoOnline/methodhandlers/HandleListSettingsMethods.php');
			exit();
			break;
		case "updateUserSettings":
			include_once('TodoOnline/methodhandlers/HandleUserSettingsMethods.php');
			exit();
			break;
		case "sendFeedbackAPI":
			include_once('TodoOnline/syncmethodhandlers/FeedbackSyncMethods.php');
			exit();
			break;
  }


  // add a check here, it their subscription is not valid, return an error
  $subscriptionLevel = TDOSubscription::getSubscriptionLevelForUserID($session->getUserId());
  if ($subscriptionLevel < SUBSCRIPTION_LEVEL_TRIAL)
  {
    outputSyncError(ERROR_CODE_EXPIRED_SUBSCRIPTION, ERROR_DESC_EXPIRED_SUBSCRIPTION);
    error_log("SyncMethodHandler method call: " . $_POST["method"] . " because user's subscription is expired: " . TDOUser::usernameForUserId($session->getUserId()));
    return;
  }

	// all methods which require authentication
	switch($method)
	{
		case "invokeGhostProtocol":
			include_once('TodoOnline/syncmethodhandlers/HandleSyncTest.php');
			exit();
			break;
    case "getEmailNotificationInfo":
      include_once('TodoOnline/syncmethodhandlers/HandleEmailNotificationSyncMethods.php');
      exit();
      break;
    case "applyDefaultNotificationSettingsToAllLists":
      include_once('TodoOnline/methodhandlers/HandleUserSettingsMethods.php');
      exit();
      break;
		case "getCompletedTasksAPI":
		case "getCompletedSubtasksAPI":
		case "getCompletedTaskitosAPI":
			include_once('TodoOnline/methodhandlers/HandleGetCompletedTasks.php');
			exit();
			break;
		default:
      outputSyncError(ERROR_CODE_INVALID_METHOD, ERROR_DESC_INVALID_METHOD);
			exit();
	}

?>

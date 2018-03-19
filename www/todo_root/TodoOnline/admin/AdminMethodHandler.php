<?php

	$method = NULL;

	if(isset($_POST["method"]))
		$method = $_POST["method"];
	elseif(isset($_GET["method"]))
		$method = $_GET["method"];
	else
	{
		echo "JSON ERROR";
		exit();
	}

	$_SESSION['ADMIN'] = TRUE;

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
	}

	$userID = $session->getUserId();
	$adminLevel = TDOUser::adminLevel($userID);

	// check authorization
	if( (!$session->isLoggedIn()) || ($adminLevel == ADMIN_LEVEL_NONE) )
	{
		$session->logout();

		error_log("Method was called ".$method." for user ($userID) that is not admin or authenticated");
		echo '{"success":false, "error":"authentication"}';
		exit();
	}
	else
	{
		// METHODS THAT REQUIRE AUTHENTICATION BUT NOT AUTHORIZATION GO HERE.
		switch ($method)
		{
			case "logout":
				include_once('TodoOnline/methodhandlers/HandleLogout.php');
				exit();
				break;
		}
	}


	if ($adminLevel == ADMIN_LEVEL_SUPER_ROOT)
	{
		switch ($method)
		{
			case "impersonateAccount":
				include_once('TodoOnline/admin/HandleAdminUserMethods.php');
				exit();
				break;
		}
	}

	if ($adminLevel >= ADMIN_LEVEL_ROOT)
	{
		switch ($method)
		{
			case "setAdminLevel":
				include_once('TodoOnline/admin/HandleAdminModifyAdmin.php');
				exit();
				break;
            case "getSystemTotals":
			case "getSystemStats":
				include_once('TodoOnline/admin/HandleAdminSystemMethods.php');
				exit();
				break;
            case "addSystemNotification":
            case "removeSystemNotification":
            case "getCurrentSystemNotification":
                include_once('TodoOnline/admin/HandleAdminSystemNotificationMethods.php');
                exit();
                break;
            case "wipeOutUserData":
            case "wipeOutUserAccount":
                include_once('TodoOnline/admin/HandleAdminWipeDataMethods.php');
                exit();
                break;
            case "getReferralStats":
                include_once('TodoOnline/admin/HandleAdminReferralsMethods.php');
                exit();
                break;
			case "updateSystemSetting":
				include_once('TodoOnline/admin/HandleSystemSettingsMethods.php');
				exit();
				break;
//            case "createAllMessageCenterTables":
//            case "deleteAllMessageCenterTables":
//            case "getAllMessageCenterTables":
//            case "addMessageCenterMessage":
//            case "testMessageCenterMessage":
//            case "getAllMessages":
//            case "getMessagesOfType":
//            case "updateMessageExpirationDate":
//                include_once('TodoOnline/admin/HandleAdminMessageCenterMethods.php');
//                exit();
//                break;
		}
	}

	if ($adminLevel >= ADMIN_LEVEL_DEVELOPER)
	{
		switch ($method)
		{
			case "createUser":
				include_once('TodoOnline/methodhandlers/HandleCreateUser.php');
				exit();
				break;
		}
	}

	if ($adminLevel >= ADMIN_LEVEL_SUPPORT)
	{
		switch ($method)
		{
			case "searchUsers":
            case "getUserInfo":
			case "adjustExpirationDate":
			case "clearBounceEmail":
			case "mailPurchaseReceipt":
			case "sendResetPasswordEmail":
			case "convertToAppleIAP":
			case "convertToGoogleIAP":
			case "attemptIAPAutorenewal":
				include_once('TodoOnline/admin/HandleAdminUserMethods.php');
				exit();
				break;
			case "searchTeams":
			case "getTeamInfo":
			case "adjustTeamExpirationDate":
				include_once('TodoOnline/admin/HandleAdminTeamMethods.php');
				exit();
				break;
            case "getAllGiftCodes":
                include_once('TodoOnline/admin/HandleAdminGiftCodeMethods.php');
                exit();
                break;
			case "listPromoCodes":
			case "listUsedPromoCodes":
			case "createPromoCode":
			case "deletePromoCode":
				include_once('TodoOnline/admin/HandlePromoCodeMethods.php');
				exit();
				break;
            case "enableUserReMigration":
                include_once('TodoOnline/admin/HandleAdminWipeDataMethods.php');
                exit();
                break;
		}
	}

	// IF THE CODE GETS THIS FAR, THE USER IS NOT AUTHORIZED TO MAKE ANY OTHER
	// CALLS.
	$session->logout();
	error_log("Method was called ".$method." for user ($userID, admin level = $adminLevel) that is not authorized");
	echo "JSON ERROR, bad method";
	exit();

?>

#!/usr/bin/php -q
<?php

include_once('TodoOnline/base_sdk.php');

function getExpirationDateForUserID($userid, $link)
{
	$sql = "SELECT FROM_UNIXTIME(expiration_date) AS exp_date FROM tdo_subscriptions WHERE userid='" . $userid . "'";
	$result = mysql_query($sql, $link);
	if (($result) && ($row = mysql_fetch_array($result)))
	{
		$expirationDate = $row['exp_date'];
		return $expirationDate;
	}

	return "";
}

function getLastPaymentTypeForUserID($userid, $link)
{
	$sql = "SELECT payment_system_type FROM tdo_user_payment_system WHERE userid='" . $userid . "'";
	$result = mysql_query($sql, $link);
	if (($result) && ($row = mysql_fetch_row($result)))
	{
		$paymentType = $row[0];
		switch($paymentType)
		{
			case 1:
				return "Stripe (autorenewing)";
				break;
			case 2:
				return "Old In-App Purchase";
				break;
			case 3:
				return "PayPal";
				break;
			case 4:
				return "Apple In-App Purchase (autorenewing)";
				break;
			case 5:
				return "Google In-App Purchase (autorenewing)";
				break;
			case 6:
				return "Team Member";
				break;
			case 7:
				return "Whitelisted";
				break;
			default:
				return "Unknown";
				break;
		}
	}

	return "Unknown";
}


function getNumberOfDevicesForUser($userid, $link)
{
	$sql = "SELECT COUNT(*) FROM tdo_user_devices WHERE userid='" . $userid . "'";
	$result = mysql_query($sql, $link);
	if (($result) && ($row = mysql_fetch_row($result)))
	{
		return $row[0];
	}

	return 0;
}


function getLastSyncDateForUser($userid, $link)
{
	$sql = "SELECT FROM_UNIXTIME(timestamp) FROM tdo_user_devices WHERE userid='" . $userid . "' ORDER BY timestamp DESC LIMIT 1";
	$result = mysql_query($sql, $link);
	if (($result) && ($row = mysql_fetch_row($result)))
	{
		return $row[0];
	}

	return "";
}


$link = TDOUtil::getDBLink();
if (!$link)
{
        echo "Could not get a connection to the databsae. Exiting...\n";
        exit(1);
}

echo "userid,email,signup_date,email_verified,email_opt_out,expiration_date,last_payment_type,num_of_all_lists,num_of_owned_lists,num_of_shared_lists,num_of_active_tasks,num_of_completed_tasks,num_of_devices,last_sync_date\n";

#$sql = "SELECT userid,username,email_verified,email_opt_out,FROM_UNIXTIME(creation_timestamp) AS sign_up_date FROM tdo_user_accounts LIMIT 10";
$sql = "SELECT userid,username,email_verified,email_opt_out,FROM_UNIXTIME(creation_timestamp) AS sign_up_date FROM tdo_user_accounts";
$result = mysql_query($sql, $link);
if ($result)
{
        while ($row = mysql_fetch_row($result))
        {
		// Skip user accounts that are from @appigo.com because
		// they very likely contain test accounts.

		$email = $row[1];
		if (strpos($email, '@appigo.com') !== FALSE)
		{
			continue; // Skip this account
		}

		$userid = $row[0];
		$emailVerified = $row[2];
		$emailOptOut = $row[3];
		$signUpDate = $row[4];

		$expirationDate = getExpirationDateForUserID($userid, $link);

		$lastPaymentType = getLastPaymentTypeForUserID($userid, $link);

		$numOfAllLists = TDOList::getListCountForUser($userid);
		$numOfOwnedLists = TDOList::getOwnedListCountForUser($userid);
		$numOfSharedLists = TDOList::getSharedListCountForUser($userid);

		$numOfActiveTasks = TDOTask::getTaskCountForUser($userid, false);
		$numOfCompletedTasks = TDOTask::getTaskCountForUser($userid, true);

		$numOfDevices = getNumberOfDevicesForUser($userid, $link);
		$lastSyncDate = getLastSyncDateForUser($userid, $link);

		echo $userid . "," . $email . "," . $signUpDate . "," . $emailVerified . "," . $emailOptOut . "," . $expirationDate . "," . $lastPaymentType . "," . $numOfAllLists . "," . $numOfOwnedLists . "," . $numOfSharedLists . "," . $numOfActiveTasks . "," . $numOfCompletedTasks . "," . $numOfDevices . "," . $lastSyncDate . "\n";
	}
}

TDOUtil::closeDBLink($link);


?>

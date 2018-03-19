#!/usr/bin/php -q
<?php

	// Include Classes
	include_once('TodoOnline/base_sdk.php');

	function promptAndGetUsername($prompt = "Username: ")
	{
		echo $prompt;
		$line = fgets(STDIN);
		return trim($line);
	}

	function promptForItem($prompt)
	{
		echo $prompt . ": ";
		$line = fgets(STDIN);
		return trim($line);
	}

	function promptAndGetTeamID()
	{
		echo "Team ID: ";
		$line = fgets(STDIN);
		return trim($line);
	}

	function promptForInput($prompt="Input: ")
	{
		echo $prompt;
		$line = fgets(STDIN);
		return trim($line);
	}

	function randomPassword()
	{
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}

	function showUserLists($userid, $deletedOnly = false, $showLineNumbers = false)
	{
		$numberedLists = array();
		$listIndex = 0;
		$lists = TDOList::getListsForUser($userid, true); // included deleted lists (true)
		if (empty($lists))
		{
			echo "Could not locate any lists for username (" . USERNAME . ")\n";
			exit(2);
		}

		if ($deletedOnly == false)
		{
			echo "Active Lists:\n";
			foreach($lists as $list)
			{
				if ($list->deleted() == false)
				{
					$listIndex++;
					$numberedLists[$listIndex] = $list;

					// Determine active and completed task counts
					$activeTaskCount = TDOTask::taskCountForList($list->listId(), $userid, false, true, NULL, NULL, false, NULL);
					$completedTaskCount = TDOTask::taskCountForList($list->listId(), $userid, true);

					if ($showLineNumbers)
						echo $listIndex . ". ";

					echo $list->listId() . '  ' . $list->name() . " (active: $activeTaskCount, completed: $completedTaskCount)\n";
				}
			}
		}

		echo "\nDeleted Lists:\n";
		foreach($lists as $list)
		{
			if ($list->deleted() == true)
			{
				$listIndex++;
				$numberedLists[$listIndex] = $list;

				if ($showLineNumbers)
					echo $listIndex . ". ";

				echo $list->listId() . '  ' . $list->name() . "  " . date(DateTime::ISO8601, $list->timestamp()) . "\n";
			}
		}

		return $numberedLists;
	}

	function showAdminTeams($adminUserID, $showLineNumbers = false)
	{
		$numberedTeams = array();
		$teamIndex = 0;
		$teams = TDOTeamAccount::getTeamsForTeamAdmin($adminUserID);
		if (empty($teams))
		{
			echo "Could not locate any teams for admin user id: " . $adminUserID . "\n";
			return NULL;
		}

		echo "\nTeams:\n";
		foreach ($teams as $team)
		{
			$teamIndex++;
			$numberedTeams[$teamIndex] = $team;

			if ($showLineNumbers)
				echo $teamIndex . ". ";

			echo $team->getTeamID() . " " . $team->getTeamName() . "\n";
		}

		return $numberedTeams;
	}

	function showTeamInfo($team)
	{
		echo "==== TEAM: " . $team->getTeamName() . " (" . $team->getTeamID() . ") ====\n";
		echo "    License Count: " . $team->getLicenseCount() . "\n";
		echo "New License Count: " . $team->getNewLicenseCount() . "\n";
		echo "  Licenses in Use: " . TDOTeamAccount::getCurrentTeamMemberCount($team->getTeamID()) . "\n";
		$billingUserID = $team->getBillingUserID();
		$billingDisplayName = TDOUser::displayNameForUserId($billingUserID);
		$billingUsername = TDOUser::usernameForUserId($billingUserID);
		echo "    Billing Admin: " . $billingDisplayName . " (" . $billingUsername . ")\n";
		echo "  Expiration Date: " . date(DateTime::ISO8601, $team->getExpirationDate()) . "\n";
		echo "    Creation Date: " . date(DateTime::ISO8601, $team->getCreationDate()) . "\n";
		echo "    Modified Date: " . date(DateTime::ISO8601, $team->getModifiedDate()) . "\n";
		echo "Billing Frequency: ";
		$billingFrequency = $team->getBillingFrequency();
		if ($billingFrequency == SUBSCRIPTION_TYPE_MONTH)
		{
			echo "Monthly\n";
		}
		else
		{
			echo "Yearly\n";
		}

		// Show the team admins
		echo "\n---- Admin Users ----\n";
		$adminUserIDs = TDOTeamAccount::getAdminUserIDsForTeam($team->getTeamID());
		foreach($adminUserIDs as $adminUserID)
		{
			$adminDisplayName = TDOUser::displayNameForUserId($adminUserID);
			$adminUsername = TDOUser::usernameForUserId($adminUserID);
			echo $adminDisplayName . " (" . $adminUsername . ")\n";
		}

		// Show the team members
		echo "\n---- Members ----\n";
		$teamMembers = TDOTeamAccount::getUserIDsForTeam($team->getTeamID());
		foreach($teamMembers as $memberUserID)
		{
			$displayName = TDOUser::displayNameForUserId($memberUserID);
			$username = TDOUser::usernameForUserId($memberUserID);
			echo $displayName . " (" . $username . ")\n";
		}

		echo "================================\n\n";
	}

	function printTeamInfoToFile($team, $fileHandle, $outputHeaderRow=false)
	{
		if ($outputHeaderRow) {
			fwrite($fileHandle, "\"Team ID\";\"Team Name\";\"License Count\";\"New License Count (for next renewal)\";\"Licenses in Use\";\"Billing User ID\";\"Billing User Email\";\"Billing User Name\";\"Phone Number\";\"Creation Date\";\"Expiration Date\";\"Last Modified Date\";\"Billing Frequency\";\"Country\";\"Billing Occurences\";\"Discovery Method\";\"Recurring Price\";\"Total Charges\"\n");
		}

		fwrite($fileHandle, "\"" . $team->getTeamID() . "\";");
		fwrite($fileHandle, "\"" . $team->getTeamName() . "\";");
		fwrite($fileHandle, "\"" . $team->getLicenseCount() . "\";");
		fwrite($fileHandle, "\"" . $team->getNewLicenseCount() . "\";");
		fwrite($fileHandle, "\"" . TDOTeamAccount::getCurrentTeamMemberCount($team->getTeamID()) . "\";");
		$billingUserID = $team->getBillingUserID();
		$billingDisplayName = TDOUser::displayNameForUserId($billingUserID);
		$billingUsername = TDOUser::usernameForUserId($billingUserID);
		$billingPhoneNumber = $team->getBizPhone();
		fwrite($fileHandle, "\"" . $billingUserID . "\";");
		fwrite($fileHandle, "\"" . $billingUsername . "\";");
		fwrite($fileHandle, "\"" . $billingDisplayName . "\";");
		fwrite($fileHandle, "\"" . $billingPhoneNumber . "\";");
		fwrite($fileHandle, "\"" . date(DateTime::ISO8601, $team->getCreationDate()) . "\";");
		fwrite($fileHandle, "\"" . date(DateTime::ISO8601, $team->getExpirationDate()) . "\";");
		fwrite($fileHandle, "\"" . date(DateTime::ISO8601, $team->getModifiedDate()) . "\";");
		$billingFrequency = $team->getBillingFrequency();
		if ($billingFrequency == SUBSCRIPTION_TYPE_MONTH)
		{
			fwrite($fileHandle, "\"Monthly\";");
		}
		else
		{
			fwrite($fileHandle, "\"Yearly\";");
		}
		fwrite($fileHandle, "\"" . $team->getBizCountry() . "\";");
		// Determine how many purchases the team has made
		$totalCharges = 0;
		$teamPurchaseHistory = TDOTeamAccount::getTeamPurchaseHistory($team->getTeamID());
		if (empty($teamPurchaseHistory))
		{
			fwrite($fileHandle, "\"0\";");
		}
		else
		{
			$numOfPurchases = count($teamPurchaseHistory);
			fwrite($fileHandle, "\"" . $numOfPurchases . "\";");

			if ($numOfPurchases > 0) {
				$lastPurchase = $teamPurchaseHistory[$numOfPurchases - 1];
				$recurringPrice = $lastPurchase["amount"];

				$recurringPrice = strtok($recurringPrice, " ");
				$recurringPrice = substr($recurringPrice, 1);

				foreach ($teamPurchaseHistory as $purchase) {
					$amount = $purchase["amount"];
					$amount = strtok($amount, " ");
					$amount = substr($amount, 1);
					$totalCharges += $amount;
				}
			}
		}
		fwrite($fileHandle, "\"" . $team->getDiscoveryAnswer() . "\";");

		// Determine the recurring price by asking the system for the unit price
		$isGrandfatheredTeam = TDOTeamAccount::isGrandfatheredTeam($team->getTeamID());
		$recurringPrice = TDOTeamAccount::unitCostForBillingFrequency($billingFrequency, $isGrandfatheredTeam);
		fwrite($fileHandle, "\"" . $recurringPrice . "\";");

		fwrite($fileHandle, "\"" . $totalCharges . "\";");

		fwrite($fileHandle, "\n");
	}

	function restoreNotificationsForTask($taskid, $link)
	{
		$notifications = TDOTaskNotification::getNotificationsForTask($taskid, true, $link);
		if (empty($notifications))
			return true;

		foreach ($notifications as $notification)
		{
			if (!$notification->deleted())
				continue;

			// For any notification that is deleted, set it to be not deleted and then
			// after doing this, call updateNotificationsForTask and it will evaluate
			// the timestamps accordingly.
			$notification->setDeleted(0);
			if (!$notification->updateTaskNotification($link))
			{
				echo "Error updating task notification: " . $notification->notificationId() . "\n";
				return false;
			}
		}

		if (!TDOTaskNotification::updateNotificationsForTask($taskid))
		{
			echo "Error restoring notifications for task: " . $taskid . "\n";
			return false;
		}

		return true;
	}

	function allTaskitosForParentTask($taskid, $link)
	{
		$sql = "SELECT * FROM tdo_taskitos WHERE parentid='$taskid'";
		$result = mysql_query($sql, $link);
		if (!$result)
			return false;

		$taskitos = array();
		while ($row = mysql_fetch_array($result))
		{
			if ((empty($row) == false) && (count($row) > 0))
			{
				$taskito = TDOTaskito::taskitoFromRow($row);
				$taskitos[] = $taskito;
			}

		}
		return $taskitos;
	}

	function restoreTaskWithID($taskid, $link)
	{
		if ((empty($taskid)) || (empty($link)))
			return false;

		$task = TDOTask::getTaskForTaskId($taskid, $link);
		if (!$task)
		{
			echo "Task not found: " . $taskid . "\n";
			return false;
		}

		// Don't restore this task if it's already restored
		if ($task->deleted() == false)
			return true;

		// Delete the task from the deleted tasks table
		$escapedTaskID = mysql_real_escape_string($taskid, $link);
		$sql = "DELETE FROM tdo_deleted_tasks WHERE taskid='$escapedTaskID'";
		if (!mysql_query($sql, $link))
		{
			echo "Error deleting the task from the deleted tasks table: " . $task->name() . " (" . $task->taskId() . ")\n";
			return false;
		}

		// Set the task as NOT deleted and re-add it, causing it to be added to the normal tasks table
		$task->setDeleted(0);
		$task->setTimestamp(time());
		if ($task->addObject($link) == false)
		{
			echo "Error adding the deleted task back into the normal tasks table: " . $task->name() . " (" . $task->taskId() . ")\n";
			return false;
		}

		if ($task->isProject())
		{
			$subtasks = TDOTask::getAllSubtasksForTask($taskid, $link);
			if ($subtasks !== false)
			{
				foreach ($subtasks as $subtask)
				{
					if (restoreTaskWithID($subtask->taskId(), $link) == false)
					{
						echo "Error restoring subtask: " . $subtask->name() . " (" . $subtask->taskId() . ")\n";
						return false;
					}
				}
			}
		}
		else if ($task->isChecklist())
		{
			$taskitos = allTaskitosForParentTask($task->taskId(), $link);
			if ($taskitos !== false)
			{
				foreach ($taskitos as $taskito)
				{
					$taskito->setDeleted(0);
					if ($taskito->updateObject($link) == false)
					{
						echo "Error restoring taskito: " . $taskito->name() . " (" . $taskito->taskitoId() . ")\n";
						return false;
					}
				}
			}
		}

		// Restore any notifications
		if (!restoreNotificationsForTask($task->taskId(), $link))
		{
			return false;
		}

		// Restore comments
		$comments = TDOComment::getCommentsForItem($task->taskId(), true);
		if (!empty($comments))
		{
			foreach ($comments as $comment)
			{
				$sql = "UPDATE tdo_comments SET deleted=0 WHERE commentid='" . $comment->commentId() ."'";
				if (!mysql_query($sql, $link))
				{
					echo "Error restoring comment (" . $comment->commentId() . ") for task: " . $task->taskId() . ": " . mysql_error() . "\n";
					return false;
				}
			}
		}

		return true;
	}


	function getAnalysisItemCount($lowerCaseItemName, $tableName, $link)
	{
		$sql = "SELECT count FROM $tableName WHERE name='$lowerCaseItemName'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			return false;
		}

		$row = mysql_fetch_array($result);
		$count = $row[0];

		return $count;
	}

	function setAnalysisItemCount($lowerCaseItemName, $tableName, $count, $link)
	{
		$sql = "INSERT INTO $tableName (name, count) VALUES('$lowerCaseItemName', $count) ON DUPLICATE KEY UPDATE name=VALUES(name), count=VALUES(count)";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			return false;
		}

		return true;
	}

	function incrementAnalysisItemCount($lowerCaseItemName, $tableName, $link)
	{
		$currentCount = getAnalysisItemCount($lowerCaseItemName, $tableName, $link);
		$newCount = 1;
		if (!empty($currentCount))
		{
			$newCount = $currentCount + 1;
		}
		setAnalysisItemCount($lowerCaseItemName, $tableName, $newCount, $link);
	}

	function getOptedInUsersWithDateRange($startTimestamp, $endTimestamp, $link)
	{
		//$sql = "SELECT * FROM tdo_user_accounts WHERE creation_timestamp >= $startTimestamp AND creation_timestamp < $endTimestamp AND email_opt_out = 0 ORDER BY creation_timestamp";
		$sql = "SELECT * FROM tdo_user_accounts WHERE creation_timestamp >= $startTimestamp AND creation_timestamp < $endTimestamp AND email_opt_out = 0 ORDER BY username";
		$result = mysql_query($sql, $link);
		if (!$result)
			return false;

		$users = array();
		while ($row = mysql_fetch_array($result))
		{
			$user = TDOUser::userFromRow($row);
			if ($user)
			{
				$users[] = $user;
			}
		}
		return $users;
	}


	function adminTool()
	{
		echo "**************************\n";
		echo "**** TODO CLOUD ADMIN ****\n";
		echo "**************************\n";
		echo " 1. Show a user's lists\n";
		echo " 2. Restore a user's deleted list (one list, user must be list owner)\n";
		echo " 3. Create a new user\n";
		echo " 4. Change a user's expiration date\n";
		echo " 5. Show a user's payment history\n";
		echo " 6. Show a user's Stripe charges\n";
		echo " 7. Show a user's autorenewal count\n";
		echo " 8. Reset a user's autorenewal count\n";
		echo " 9. List teams\n";
		echo "10. Create a new team\n";
		echo "11. View team info\n";
		echo "12. Add user to team\n";
		echo "13. Generate email list\n";
		echo "14. Send Mandrill test emails\n";
		echo "15. Attempt IAP Autorenewal for user\n";
		echo "16. Show all teams\n";
		echo "17. Generate email list (email only)\n";
		echo "18. Export info about \"Highly Active People\"\n";
		echo "19. Send Mandrill onboarding email to date range\n";
		echo "20. Export teams to CSV file\n";
		echo "21. Archive completed tasks from a list\n";
		echo "\n\n";
		echo "Select an option and press <ENTER> (empty to quit): ";

		$line = fgets(STDIN);
		if (!$line || empty($line) || strlen(trim($line)) == 0)
		{
			//echo "Empty response\n";
			exit(1);
		}

		if (trim($line) == "1")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			showUserLists($userid);
			echo "\n";
		}
		else if (trim($line) == "2")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			$lists = showUserLists($userid, true, true);

			if (!$lists || empty($lists) || count($lists) == 0)
			{
				echo "No deleted lists found for user\n";
				adminTool();
				return;
			}

			// Prompt the user for the list they wish to restore
			echo "\nEnter the index of the list you wish to restore: ";
			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$listIndex = trim($line);

			if (!isset($lists[$listIndex]))
			{
				echo "You specified an invalid list index.\n";
				adminTool();
				return;
			}

			$list = $lists[$listIndex];

			echo "You've specified the list \"" . $list->name() . "\" (" . $list->listId() . "). Type \"yes\" to proceed: ";

			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$response = strtolower(trim($line));
			if ($response != "yes")
			{
				echo "Aborting (you didn't type \"yes\")\n";
				adminTool();
				return;
			}

			// Check to see if this user is an owner of this list
			$userRole = TDOList::getRoleForUser($list->listId(), $userid);

			if ($userRole != LIST_MEMBERSHIP_OWNER)
			{
				echo "This user is not an owner of the specified list.\n";
				adminTool();
				return;
			}

			// Do all of this in a transaction so a full restore happens or nothing at all
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			if (!mysql_query("START TRANSACTION", $link))
			{
				echo "Could not start a DB transaction. Exiting...\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			// Restore the list first
			if (!mysql_query("UPDATE tdo_lists SET deleted=0, timestamp='" . time() . "' WHERE listid='" . $list->listId() . "'", $link))
			{
				echo "Could not mark the list as undeleted, rolling back...\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				echo "Rollback completed. Restarting...\n\n";
				adminTool();
				return;
			}

			// Restore all the tasks that belong to the list
			$sql = "SELECT taskid FROM tdo_deleted_tasks WHERE listid='" . $list->listId() . "' AND (parentid = '' OR parentid IS NULL)";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				echo "Error querying deleted tasks, rolling back...\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				echo "Rollback completed. Restarting...\n\n";
				adminTool();
				return;
			}

			$deletedTasks = array();
			while ($row = mysql_fetch_array($result))
			{
				if ((empty($row['taskid']) == false) && (count($row) > 0))
				{
					$taskid = $row['taskid'];
					$deletedTasks[] = $taskid;
				}
			}

			foreach ($deletedTasks as $taskid)
			{
				if (!restoreTaskWithID($taskid, $link))
				{
					echo "Failed to restore taskid: " . $taskid . "\n";
					mysql_query("ROLLBACK", $link);
					TDOUtil::closeDBLink($link);
					echo "Rollback completed. Restarting...\n\n";
					adminTool();
					return;
				}
			}

			// Update the timestamps on the list
			TDOList::updateTaskTimestampForList($list->listId(), time(), $link);

			if (!mysql_query("COMMIT", $link))
			{
				echo "Couldn't commit the transaction to restore the list: " . $list->listId() . " Error: " . mysql_error() . "\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				echo "Rollback completed. Restarting...\n\n";
				adminTool();
				return;
			}

			TDOUtil::closeDBLink($link);

			//TDOChangeLog::addChangeLog($list->listId(), $userid, $list->listId(), $list->name(), ITEM_TYPE_LIST, CHANGE_TYPE_RESTORE, CHANGE_LOCATION_WEB);
			TDOChangeLog::addChangeLog($list->listId(), "admin-console", $list->listId(), $list->name(), ITEM_TYPE_LIST, CHANGE_TYPE_RESTORE, CHANGE_LOCATION_WEB);


			echo "Successfully restored \"" . $list->name() . "\" (" . $list->listId() . ").\n\n";
		}
		else if (trim($line) == "3")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (!empty($userid))
			{
				echo "\nThis user already exists!\n\n";
				adminTool();
				return;
			}

			$firstname = promptForInput("First name: ");
			if (empty($firstname))
			{
				echo "\nEmpty first name!\n\n";
				adminTool();
				return;
			}

			$lastname = promptForInput("Last name: ");
			if (empty($lastname))
			{
				echo "\nEmpty last name!\n\n";
				adminTool();
				return;
			}

//		PASSWORD:
			$password = promptForInput("Password (blank for randomly generated): ");
			if (empty($password))
			{
				// Generate a randomly generated
				$password = randomPassword();
				echo "Password: $password\n";
			}

//			goto PASSWORD;

		EXPIRYDATE:

			$expirationDate = new DateTime();
			$expirationDate->add(new DateInterval("P14D"));
			$dateString = promptForInput("Expiration date (MM/DD/YYYY - blank for 14 days from now): ");
			if (!empty($dateString))
			{
				$expirationDate = DateTime::createFromFormat("m/d/Y", $dateString);
				if (empty($expirationDate))
				{
					echo "\nUnable to parse a date!\n\n";
					goto EXPIRYDATE;
				}

				// Add our timezone information onto the date so we get what we expect
				$expirationDate->setTimezone(new DateTimeZone("America/Denver"));
			}

			echo "Expiration date: " . $expirationDate->format(DateTime::ISO8601) . "\n";

			echo "New user:\n";
			echo "\t       Username: $username\n";
			echo "\t       Password: $password\n";
			echo "\t     First Name: $firstname\n";
			echo "\t      Last Name: $lastname\n";
			echo "\tExpiration Date: " . $expirationDate->format(DateTime::ISO8601) . "\n";

		CREATE_USER_YES_PROMPT:
			echo "Create this user? Type 'yes' to continue: ";

			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				goto CREATE_USER_YES_PROMPT;
			}

			$response = strtolower(trim($line));
			if ($response != "yes")
			{
				echo "Aborting (you didn't type \"yes\")\n";
				adminTool();
				return;
			}

			$user = new TDOUser();
			$user->setUsername($username);
			$user->setPassword($password);
			$user->setFirstName($firstname);
			$user->setLastName($lastname);
			$user->setEmailOptOut(1);

			if (!$user->addUser())
			{
				echo "ERROR creating the user!\n";
				adminTool();
				return;
			}

			echo "User created successfully...updating expiration date\n";

			// Update the user's subscription

			$userid = $user->userId();
			$subscriptionid = TDOSubscription::getSubscriptionIDForUserID($userid);
			if (empty($subscriptionid))
			{
				echo "ERROR: Cannot find a subscription id for the newly-created user.\n";
				adminTool();
				return;
			}

			if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionid, $expirationDate->getTimestamp(), "year", SUBSCRIPTION_LEVEL_PROMO))
			{
				echo "ERROR Updating the user's subscription!\n";
				adminTool();
				return;
			}

			echo "Successfully updated the user's expiration date!\n";

			// Now send the user a password reset email
			if(TDOPasswordReset::deleteExistingPasswordResetForUser($userid) == false)
			{
				echo "Unable to invalidate existing reset password request for user\n";
				adminTool();
				return;
			}

			$passwordReset = new TDOPasswordReset();
			$passwordReset->setUserId($userid);
			$passwordReset->setUsername($username);

			if($passwordReset->addPasswordReset())
			{
				$email = TDOMailer::validate_email($username);
				if($email)
				{
					$userDisplayName = TDOUser::displayNameForUserId($userid);
					if(empty($userDisplayName))
						$userDisplayName = "Appigo Support";

					//$resetURL = SITE_PROTOCOL . SITE_BASE_URL."?resetpassword=true&resetid=".$passwordReset->resetId()."&uid=".$userid;
					$resetURL = "https://www.todo-cloud.com/?resetpassword=true&resetid=".$passwordReset->resetId()."&uid=".$userid;
					if(TDOMailer::sendResetPasswordEmail($userDisplayName, $email, $resetURL, true))
					{
						echo "Successfully reset the user's password!\n";
					}
					else
					{
						echo "COULD NOT reset the user's password!\n";
						adminTool();
						return;
					}
				}
			}
		}
		else if (trim($line) == "4")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "\nThis user doesn't exist!\n\n";
				adminTool();
				return;
			}

		ADJUST_EXPIRYDATE:

			$expirationDate = new DateTime();
			$expirationDate->add(new DateInterval("P14D"));
			$dateString = promptForInput("Expiration date (MM/DD/YYYY - blank for 14 days from now): ");
			if (!empty($dateString))
			{
				$expirationDate = DateTime::createFromFormat("m/d/Y", $dateString);
				if (empty($expirationDate))
				{
					echo "\nUnable to parse a date!\n\n";
					goto ADJUST_EXPIRYDATE;
				}

				// Add our timezone information onto the date so we get what we expect
				$expirationDate->setTimezone(new DateTimeZone("America/Denver"));
			}

			echo "Expiration date: " . $expirationDate->format(DateTime::ISO8601) . "\n";

		EXPIRATION_YES_PROMPT:
			echo "Type 'yes' to continue: ";

			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				goto EXPIRATION_YES_PROMPT;
			}

			$response = strtolower(trim($line));
			if ($response != "yes")
			{
				echo "Aborting (you didn't type \"yes\")\n";
				adminTool();
				return;
			}

			$subscriptionid = TDOSubscription::getSubscriptionIDForUserID($userid);
			if (empty($subscriptionid))
			{
				echo "ERROR: Cannot find a subscription id for the newly-created user.\n";
				adminTool();
				return;
			}

			if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionid, $expirationDate->getTimestamp(), "year", SUBSCRIPTION_LEVEL_PROMO))
			{
				echo "ERROR Updating the user's subscription!\n";
				adminTool();
				return;
			}

			echo "\nSuccessfully updated the user's expiration date!\n\n";

			$username = TDOUser::usernameForUserId($userid);
			$displayName = TDOUser::displayNameForUserId($userid);
			if ($username)
			{
				TDOMailer::notifyUserOfExpirationChange($username, $displayName, $expirationDate->getTimestamp());
			}
		}
		else if (trim($line) == "5")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "\nThis user doesn't exist!\n\n";
				adminTool();
				return;
			}

			$purchaseHistory = TDOSubscription::getPurchaseHistoryForUserID($userid);
			if (empty($purchaseHistory))
			{
				echo "\nNO payment history found.\n\n";
				adminTool();
				return;
			}

			echo "\n\nPurchase Date, Subscription Type, Description\n";
			foreach($purchaseHistory as $purchase)
			{
				echo date(DateTime::ISO8601, $purchase['timestamp']) . ", ";
				echo $purchase['subscriptionType'] . ", " . $purchase['description'] . "\n";
			}
		}
		else if (trim($line) == "6")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "\nThis user doesn't exist!\n\n";
				adminTool();
				return;
			}

			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			$sql = "SELECT stripe_userid,stripe_chargeid,card_type,last4,timestamp FROM tdo_stripe_payment_history WHERE userid='$userid' ORDER BY timestamp DESC";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				echo "\n\nPurchase Date, Customer ID, Charge ID, Card Type, Last4\n";
				while ($row = mysql_fetch_array($result))
				{
					echo date(DateTime::ISO8601, $row['timestamp']) . ", " . $row['stripe_userid'] . ", " . $row['stripe_chargeid'] . ", " . $row['card_type'] . ", " . $row['last4'] . "\n";
				}
			}
			echo "\n";

			TDOUtil::closeDBLink($link);
		}
		else if (trim($line) == "7")
		{
                        $username = promptAndGetUsername();
                        if (empty($username))
                        {
                                echo "Empty username\n";
                                adminTool();
                                return;
                        }
                        $userid = TDOUser::userIdForUserName($username);
                        if (empty($userid))
                        {
                                echo "\nThis user doesn't exist!\n\n";
                                adminTool();
                                return;
                        }

                        $subscriptionid = TDOSubscription::getSubscriptionIDForUserID($userid);
                        if (empty($subscriptionid))
                        {
                                echo "\nCould not find the subscription id for this user!\n\n";
                                adminTool();
                                return;
                        }

                        $link = TDOUtil::getDBLink();
                        if (!$link)
                        {
                                echo "Could not get a connection to the database. Exiting...\n";
                                exit(1);
                        }

                        $sql = "SELECT renewal_attempts,attempted_time,failure_reason FROM tdo_autorenew_history WHERE subscriptionid='$subscriptionid'";
                        $result = mysql_query($sql, $link);
                        if ($result)
                        {
				while ($row = mysql_fetch_array($result))
				{
					echo "\n\nRenewal Attempts: " . $row['renewal_attempts'] . "\n";
					echo "  Attempted Time: " . date(DateTime::ISO8601, $row['attempted_time']) . "\n";
					echo "  Failure Reason: " . $row['failure_reason'] . "\n\n";
				}
                        }

                        TDOUtil::closeDBLink($link);
		}
		else if (trim($line) == "8")
		{
                        $username = promptAndGetUsername();
                        if (empty($username))
                        {
                                echo "Empty username\n";
                                adminTool();
                                return;
                        }
                        $userid = TDOUser::userIdForUserName($username);
                        if (empty($userid))
                        {
                                echo "\nThis user doesn't exist!\n\n";
                                adminTool();
                                return;
                        }

                        $subscriptionid = TDOSubscription::getSubscriptionIDForUserID($userid);
                        if (empty($subscriptionid))
                        {
                                echo "\nCould not find the subscription id for this user!\n\n";
                                adminTool();
                                return;
                        }

                        if (TDOSubscription::removeSubscriptionFromAutorenewQueue($subscriptionid) == false)
                        {
                                echo "\nUnable to remove the subscription from the autorenew queue (it may not have been in there).\n\n";
                                adminTool();
                                return;
                        }
                        else
                        {
                                echo "\nRemoved successfully!\n\n";
                                adminTool();
                                return;
                        }
		}
		else if (trim($line) == "9")
		{
			$searchString = promptForItem("Team name or business name");
			if (empty($searchString))
			{
				echo "Empty name\n";
				adminTool();
				return;
			}
			$teams = TDOTeamAccount::getTeamsForSearchString($searchString, 100, 0);
			if (empty($teams) || count($teams) == 0)
			{
				echo "No teams found\n";
				adminTool();
				return;
			}

			$index = 0;
			foreach ($teams as $team)
			{
				$billingUsername = TDOUser::usernameForUserId($team->getBillingUserID());

				$index++;
				echo $index . ". " . $team->getTeamName() . " (" . $team->getBizName() . ": " . $billingUsername . ")\n";
			}

			$selectedIndex = promptForItem("View team details");
			if (empty($selectedIndex) || strlen($selectedIndex) == 0)
			{
				adminTool();
				return;
			}

			$selectedTeam = $teams[$selectedIndex - 1];
			if (empty($selectedTeam))
			{
				echo "Invalid team index\n";
				adminTool();
				return;
			}

			showTeamInfo($selectedTeam);
			adminTool();
			return;
		}
		else if (trim($line) == "10")
		{
			$username = promptAndGetUsername("Team admin username: ");
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			$teamName = promptForItem('Team Name');
			if (empty($teamName) || strlen($teamName) == 0)
			{
				echo "Empty team name\n";
				adminTool();
				return;
			}

			$bizName = promptForItem('Business Name');
			if (empty($bizName) || strlen($bizName) == 0)
			{
				echo "Empty business name\n";
				adminTool();
				return;
			}

			$bizPhone = promptForItem('Business Phone');
			$bizAddr1 = promptForItem('Address Line 1');
			$bizAddr2 = promptForItem('Address Line 2');
			$bizCity = promptForItem('City');
			$bizState = promptForItem('State');
			$bizCountry = promptForItem('Country (2 character code)');
			$bizPostalCode = promptForItem('Postal Code');
			$licenseCount = (int)promptForItem('License Count');
			if ($licenseCount <= 0)
			{
				echo "Invalid license count!\n";
				adminTool();
				return;
			}

			$newLicenseCount = (int)promptForItem('New License Count');
			if ($newLicenseCount <= 0)
			{
				echo "Invalid *new* license count!\n";
				adminTool();
				return;
			}

			$now = time();
			$expirationDate = promptForItem('Expiration Date (YYYY-MM-DD)');
			$expirationDate = strtotime($expirationDate);
			if ($expirationDate <= $now)
			{
				echo "Expiration date cannot be in the past.\n";
				adminTool();
				return;
			}

			$billingFrequency = promptForItem('Billing Frequency (Enter "Monthly" or "Yearly")');
			$billingFrequency = strtolower($billingFrequency);
			if ($billingFrequency != "monthly" && $billingFrequency != "yearly")
			{
				echo "Invalid billing frequency: $billingFrequency\n";
				adminTool();
				return;
			}
			if ($billingFrequency == "monthly")
			{
				$billingFrequency = SUBSCRIPTION_TYPE_MONTH;
			}
			else
			{
				$billingFrequency = SUBSCRIPTION_TYPE_YEAR;
			}

			$team = new TDOTeamAccount();
			$team->setBillingUserID($userid);
			$team->setTeamName($teamName);
			$team->setLicenseCount($licenseCount);
			$team->setExpirationDate($expirationDate);
			$team->setCreationDate($now);
			$team->setModifiedDate($now);
			$team->setNewLicenseCount($newLicenseCount);
			$team->setBizName($bizName);
			$team->setBizPhone($bizPhone);
			$team->setBizAddr1($bizAddr1);
			$team->setBizAddr2($bizAddr2);
			$team->setBizCity($bizCity);
			$team->setBizState($bizState);
			$team->setBizCountry($bizCountry);
			$team->setBizPostalCode($bizPostalCode);
			$team->setBillingFrequency($billingFrequency);

			if (!$team->addAccount())
			{
				echo "Error creating team\n";
				adminTool();
				return;
			}

			showTeamInfo($team);

			adminTool();
			return;
		}
		else if (trim($line) == "11")
		{
			$username = promptAndGetUsername("Team admin username: ");
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			$teams = showAdminTeams($userid, true);

			if (!$teams || empty($teams) || count($teams) == 0)
			{
				echo "No teams found for the admin user\n";
				adminTool();
				return;
			}

			// Prompt the user for the team to add users to
			echo "\nEnter the index of the team you wish to view: ";
			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$teamIndex = trim($line);
			if (empty($teamIndex))
			{
				echo "Empty input.\n";
				adminTool();
				return;
			}

			if (!is_numeric($teamIndex))
			{
				echo "You didn't enter a number.\n";
				adminTool();
				return;
			}

			$teamIndex = (int)$teamIndex;
			if ($teamIndex <= 0)
			{
				echo "Invalid index.\n";
				adminTool();
				return;
			}

			if (!isset($teams[$teamIndex]))
			{
				echo "You specified an invalid team index.\n";
				adminTool();
				return;
			}

			$team = $teams[$teamIndex];

			showTeamInfo($team);

			adminTool();
			return;
		}
		else if (trim($line) == "12")
		{
			$username = promptAndGetUsername("Team admin username: ");
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}
SHOW_TEAM_ADMIN_TEAMS:

			$teams = showAdminTeams($userid, true);

			if (!$teams || empty($teams) || count($teams) == 0)
			{
				echo "No teams found for the admin user\n";
				adminTool();
				return;
			}

			// Prompt the user for the team to add users to
			echo "\nEnter the index of the team you wish to add members to: ";
			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$teamIndex = trim($line);
			if (empty($teamIndex))
			{
				echo "Empty input.\n";
				adminTool();
				return;
			}

			if (!is_numeric($teamIndex))
			{
				echo "You didn't enter a number.\n";
				adminTool();
				return;
			}

			$teamIndex = (int)$teamIndex;
			if ($teamIndex <= 0)
			{
				echo "Invalid index.\n";
				adminTool();
				return;
			}

			if (!isset($teams[$teamIndex]))
			{
				echo "You specified an invalid team index.\n";
				adminTool();
				return;
			}

			$team = $teams[$teamIndex];

			echo "You've specified the team \"" . $team->getTeamName() . "\" (" . $team->getTeamID() . ").\n";

			// System integrity check to make sure the number of team members available
			// will not be exceeded.
			$currentTeamMemberCount = TDOTeamAccount::getCurrentTeamMemberCount($team->getTeamID(), TEAM_MEMBERSHIP_TYPE_MEMBER);
			$newTeamMemberCount = $currentTeamMemberCount + 1;
			if ($newTeamMemberCount > $team->getNewLicenseCount())
			{
				echo "Cannot add new users to this team because no license slots are available.\n";
				goto SHOW_TEAM_ADMIN_TEAMS;
			}

ADD_TEAM_USER_PROMPT:
			// Now prompt the user for usernames of people
			echo "=== ADD USER TO TEAM ===\n";
			$username = promptAndGetUsername("Username to add to team (" . $team->getTeamName() . "): ");
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				goto ADD_TEAM_USER_PROMPT;
			}

			// Make sure the user isn't part of an existing team
			$existingTeam = TDOTeamAccount::getTeamForTeamMember($userid);
			if (!empty($existingTeam))
			{
				echo "User is already a member of a team: " . $existingTeam->getTeamName() . "\n";
				goto ADD_TEAM_USER_PROMPT;
			}

			// Make sure the user isn't part of an IAP system
			if (TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userid))
			{
				echo "This user is part of an active in-app purchase subscription.\n";
				goto ADD_TEAM_USER_PROMPT;
			}

			// Do all of this in a transaction so a full restore happens or nothing at all
			// 1. Add the user to the team membership
			// 2. Extend the user's subscription to match the team's expiration date
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			if (!mysql_query("START TRANSACTION", $link))
			{
				echo "Could not start a DB transaction. Exiting...\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			if (!TDOTeamAccount::addUserToTeam($userid, $team->getTeamID(), TEAM_MEMBERSHIP_TYPE_MEMBER, $link))
			{
				echo "Error adding the user to the team. Please try again later.\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				goto ADD_TEAM_USER_PROMPT;
			}

			$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userid, $link);
			if (!$subscriptionID)
			{
				echo "Couldn't find a subscription for the user.\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				goto ADD_TEAM_USER_PROMPT;
			}

			$newExpirationTimestamp = $team->getExpirationDate();
			$billingFrequency = $team->getBillingFrequency();

			if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $billingFrequency, SUBSCRIPTION_LEVEL_TEAM, $team->getTeamID(), $link))
			{
				echo "Unable to update the user's subscription to match the team's subscription\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				goto ADD_TEAM_USER_PROMPT;
			}

			if (!mysql_query("COMMIT", $link))
			{
				echo "Couldn't commit the transaction to add the user to the team: "  . mysql_error() . "\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				goto ADD_TEAM_USER_PROMPT;
			}

			TDOUtil::closeDBLink($link);

			showTeamInfo($team);

			echo "Successfully added the user to the team!\n";
			goto ADD_TEAM_USER_PROMPT;
		}
		else if (trim($line) == "13")
		{
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			$sql = "SELECT username,first_name,last_name,FROM_UNIXTIME(creation_timestamp),email_verified,email_opt_out FROM tdo_user_accounts";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				echo "No users";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			$outputFile = fopen("./todo-cloud-users.csv", "w");
			if (!$outputFile)
			{
				echo "Could not create output file\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			fwrite($outputFile, "\"username\";\"first_name\";\"last_name\";\"creation_timestamp\";\"email_verified\";\"email_opt_out\"\n");

			while ($row = mysql_fetch_array($result))
			{
				fwrite($outputFile, "\"" . $row[0] . "\";\"" . $row[1] . "\";\"" . $row[2] . "\";\"" . $row[3] . "\";\"" . $row[4] . "\";\"" . $row[5] . "\"\n");
			}

			fclose($outputFile);

			echo "Successfully exported todo-cloud-users.csv\n";

			TDOUtil::closeDBLink($link);

			adminTool();
			return;
		}
		else if (trim($line) == "14")
		{
			$email = promptForItem('Send to email address');
			if (empty($email))
			{
				echo "Empty email address\n";
				adminTool();
				return;
			}

			$fromUserName = "John Hawne";
			$invitationURL = "http://www.appigo.com/bogus";
			$listName = "Bogus List";
			$teamName = "Bogus Team";
			$userDisplayName = "John Hawne";
			$resetURL = "http://www.appigo.com/bogus";
			$verifyEmailURL = "http://www.appigo.com/bogus";
			$taskCreationEmail = "bogus-email";
			$teamAdminURL = "http://www.appigo.com/bogus";
			$adminDisplayName = "John Hawne";
			$memberDisplayName = "Julie Hawne";
			$memberEmail = "julie@example.com";
			$expirationDate = date('d M Y', time());
			$displayName = $userDisplayName;
			$promoLink = "http://www.appigo.com/ben-this-is-bogus-on-purpose";
			$newExpirationDate = date("D d M Y", time());
			$errorMessage = "This is a bogus error message. Yes, Ben, this is intentional. :)";
			$accountType = "Monthly";
			$paymentDate = date("D d M Y", time());
			$paymentMethod = "VISA XXXX-XXXX-XXXX-1234";
			$newExpirationString = date("D d M Y", time());
			$termsURL = "https://www.todo-cloud.com/terms";
			$bizContactInfo = "Bogus Biz Name<br/>\n";
			$bizContactInfo .= "801-555-5555<br/>\n";
			$bizContactInfo .= "1234 Bogus Lane<br/>\n";
			$bizContactInfo .= "Boofoo, UT 84000<br/>\n";
			$bizContactInfo .= "USA<br/>\n";
			$unitPriceString = "1.99";
			$unitCombinedPriceString = "1.99";
			$subtotalString = ".99";
			$purchaseAmount = "1.99";
			$accountCreditString = "0.00";
			$newExpirationDateString = $newExpirationString;
			$totalCharge = "1.99";
			$numOfSubscriptions = "1";
			$userEmail = "bob@example.com";
			$firstName = "Bob";
			$expirationDateString = $newExpirationDateString;
			$referralStatusURL = "https://www.todo-cloud.com/?appSettings=show&option=referrals";
			$copyrightYear = date('Y');
			$urlEscapedEmailAddress = urlencode($memberEmail);
			$unsubscribeLink = "https://www.todo-cloud.com/?referralunsubscribe=yes&email=$urlEscapedEmailAddress";
			$senderDisplayName = $fromUserName;
			$referralLink = "https://www.todo-cloud.com/";
			$giftCodeItems = "<li>".$accountType." gift code for ".$memberDisplayName.":$ $totalCharge</li>";
			$senderName = $displayName;
			$message = "Bogus message about Gift Codes.";
			$recipientName = "Anne Engleberry";
			$siteURL = "http://www.todo-cloud.com/";
			$giftCodeLink = "http://www.todo-cloud.com/";
			$giftCodeMonths = "1";




			echo "List Invitation...";
			$mergeTags = array(
							   array('name' => 'FROM_USER_NAME',
									 'content' => $fromUserName),
							   array('name' => 'INVITATION_URL',
									 'content' => $invitationURL),
							   array('name' => 'SHARED_LIST_NAME',
									 'content' => $listName)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-shared-list-invitation',
												 $email,
												 null, // User Display Name
												 $mergeTags);
			echo "done\n";


			echo "Team Admin Invitation...";
			$mergeTags = array(
							   array('name' => 'FROM_USER_NAME',
									 'content' => $fromUserName),
							   array('name' => 'INVITATION_URL',
									 'content' => $invitationURL),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-admin-invitation',
												 $email,
												 null, // User Display Name
												 $mergeTags);
			echo "done\n";


			echo "Team Subscription Invitation...";
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-invitation',
												 $email,
												 null, // User Display Name
												 $mergeTags);
			echo "done\n";




			echo "Appigo Support Password reset by admin...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'RESET_URL',
									 'content' => $resetURL)
							   );
			TDOMailer::sendMandrillEmailTemplate('appigo-support-reset-password-by-administrator',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";

			echo "Appigo Support Account password reset by user...";
			TDOMailer::sendMandrillEmailTemplate('appigo-support-reset-password',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";


			echo "Todo Cloud Password reset by admin...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'RESET_URL',
									 'content' => $resetURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-reset-password-by-administrator',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";

			echo "Todo Cloud Password reset by user...";
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-reset-password',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";


			echo "Todo Cloud Welcome Email...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'VERIFY_EMAIL_URL',
									 'content' => $verifyEmailURL),
							   array('name' => 'TASK_CREATION_EMAIL',
									 'content' => $taskCreationEmail . "@newtask.todo-cloud.com")
							   );
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-welcome-email',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";




			echo "Todo Cloud VIP Email Verification...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'VERIFY_EMAIL_URL',
									 'content' => $verifyEmailURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-vip-email-verification',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";

			echo "Todo Cloud Normal User Email Verification...";

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-email-verification',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";


			echo "Todo Cloud VIP Reverify Email...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'VERIFY_EMAIL_URL',
									 'content' => $verifyEmailURL)
							   );
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-vip-reverify-email-before-expiration',
												 $email,
												 $userDisplayName,
												 $mergeTags);
			echo "done\n";



			echo "Todo Cloud Team Subscription Added...";
			$mergeTags = array(
							   array('name' => 'FROM_USER_NAME',
									 'content' => $fromUserName),
							   array('name' => 'INVITATION_URL',
									 'content' => $invitationURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-added',
														$email,
														null, // User Display Name
														$mergeTags);
			echo "done\n";

			echo "Todo Cloud Team New Admin...";
			$mergeTags = array(
							   array('name' => 'ADMIN_DISPLAY_NAME',
									 'content' => $adminDisplayName),
							   array('name' => 'MEMBER_DISPLAY_NAME',
									 'content' => $memberDisplayName),
							   array('name' => 'MEMBER_EMAIL',
									 'content' => $memberEmail),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'TEAM_ADMIN_URL',
									 'content' => $teamAdminURL)
							   );
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-admin',
												 $email,
												 $memberDisplayName, // User Display Name
												 $mergeTags);
			echo "done\n";


			echo "Todo Cloud New Team Member...";
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-member',
												 $email,
												 $memberDisplayName, // User Display Name
												 $mergeTags);
			echo "done\n";




			echo "Todo Cloud Team Removed Admin...";
			$mergeTags = array(
							   array('name' => 'ADMIN_DISPLAY_NAME',
									 'content' => $adminDisplayName),
							   array('name' => 'MEMBER_DISPLAY_NAME',
									 'content' => $memberDisplayName),
							   array('name' => 'MEMBER_EMAIL',
									 'content' => $memberEmail),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'TEAM_ADMIN_URL',
									 'content' => $teamAdminURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-admin',
															$email,
															$memberDisplayName, // User Display Name
															$mergeTags);
			echo "done\n";

			echo "Todo Cloud Team Removed Admin...";
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-member',
															$email,
															$memberDisplayName, // User Display Name
															$mergeTags);
			echo "done\n";




			echo "Todo Cloud Team Removed Admin...";
			$mergeTags = array(
							   array('name' => 'MEMBER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'EXPIRATION_DATE',
									 'content' => $expirationDate)
							   );


			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-member-account-removed',
														$email,
														$displayName, // User Display Name
														$mergeTags);
			echo "done\n";


			echo "Todo Cloud Promo Code...";
			$mergeTags = array(
							   array('name' => 'PROMO_CODE_URL',
									 'content' => $promoLink)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-promo-code',
														$email,
														null, // User Display Name
														$mergeTags);
			echo "done\n";


			echo "Todo Cloud Account Expiration Change...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'NEW_EXPIRATION_DATE',
									 'content' => $newExpirationDate)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-account-expiration-change',
														$email,
														$displayName, // User Display Name
														$mergeTags);
			echo "done\n";


			echo "Todo Cloud Team Expiration Change...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'NEW_EXPIRATION_DATE',
									 'content' => $newExpirationDate),
							   array('name' => 'TEAM_ADMIN_URL',
									 'content' => $teamAdminURL)
							   );


			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-account-expiration-change',
												 $email,
												 $displayName, // User Display Name
												 $mergeTags);
			echo "done\n";


			echo "Todo Cloud Team Expiration Change...";
			$mergeTags = array(
							   array('name' => 'ERROR_MESSAGE',
									 'content' => $errorMessage)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-comment-error',
														$email,
														null, // User Display Name
														$mergeTags);
			echo "done\n";


			echo "Todo Cloud Subcription Purchase Receipt...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'USER_EMAIL_ADDRESS',
									 'content' => $email),
							   array('name' => 'ACCOUNT_TYPE',
									 'content' => $accountType),
							   array('name' => 'PAYMENT_DATE',
									 'content' => $paymentDate),
							   array('name' => 'PAYMENT_METHOD',
									 'content' => $paymentMethod),
							   array('name' => 'NEW_EXPIRATION_DATE',
									 'content' => $newExpirationString),
							   array('name' => 'PURCHASE_AMOUNT',
									 'content' => $purchaseAmount),
							   array('name' => 'TERMS_URL',
									 'content' => $termsURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-subscription-purchase-receipt',
														$email,
														$displayName, // User Display Name
														$mergeTags);
			echo "done\n";



			echo "Todo Cloud Team Subcription Purchase Receipt...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'USER_EMAIL_ADDRESS',
									 'content' => $email),

							   array('name' => 'BIZ_CONTACT_INFO',
									 'content' => $bizContactInfo),

							   array('name' => 'ACCOUNT_TYPE',
									 'content' => $accountType),
							   array('name' => 'PAYMENT_DATE',
									 'content' => $paymentDate),
							   array('name' => 'PAYMENT_METHOD',
									 'content' => $paymentMethod),
							   array('name' => 'NEW_EXPIRATION_DATE',
									 'content' => $newExpirationString),
							   array('name' => 'PURCHASE_AMOUNT',
									 'content' => $purchaseAmount),

							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'NUM_OF_SUBSCRIPTIONS',
									 'content' => $numOfSubscriptions),
							   array('name' => 'UNIT_PRICE',
									 'content' => $unitPriceString),
							   array('name' => 'UNIT_COMBINED_PRICE',
									 'content' => $unitCombinedPriceString),
							   array('name' => 'SUBTOTAL',
									 'content' => $subtotalString),

							   array('name' => 'TERMS_URL',
									 'content' => $termsURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-purchase-receipt',
														$email,
														$displayName, // User Display Name
														$mergeTags);
			echo "done\n";



			echo "Todo Cloud Team Change Purchase Receipt...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'USER_EMAIL_ADDRESS',
									 'content' => $email),

							   array('name' => 'BIZ_CONTACT_INFO',
									 'content' => $bizContactInfo),

							   array('name' => 'PAYMENT_DATE',
									 'content' => $paymentDate),
							   array('name' => 'PAYMENT_METHOD',
									 'content' => $paymentMethod),
							   array('name' => 'NEW_EXPIRATION_DATE',
									 'content' => $newExpirationDateString),
							   array('name' => 'PURCHASE_AMOUNT',
									 'content' => $totalCharge),

							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'NUM_OF_SUBSCRIPTIONS',
									 'content' => $numOfSubscriptions),
							   array('name' => 'UNIT_PRICE',
									 'content' => $unitPriceString),
							   array('name' => 'UNIT_COMBINED_PRICE',
									 'content' => $unitCombinedPriceString),
							   array('name' => 'SUBTOTAL',
									 'content' => $subtotalString),
							   array('name' => 'ACCOUNT_CREDIT',
									 'content' => $accountCreditString),

							   array('name' => 'TERMS_URL',
									 'content' => $termsURL)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-subscription-change-receipt',
														$email,
														$displayName, // User Display Name
														$mergeTags);

			echo "done\n";



			echo "Todo Cloud Team Removed Billing Admin...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $adminDisplayName),
							   array('name' => 'TEAM_ADMIN_URL',
									 'content' => $teamAdminURL),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-removed-billing-admin',
														$email,
														$adminDisplayName, // User Display Name
														$mergeTags);
			echo "done\n";

			echo "Todo Cloud Team Removed Billing Admin...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $adminDisplayName),
							   array('name' => 'TEAM_ADMIN_URL',
									 'content' => $teamAdminURL),
							   array('name' => 'TEAM_NAME',
									 'content' => $teamName),
							   array('name' => 'NEW_ADMIN_DISPLAY_NAME',
									 'content' => $userDisplayName),
							   array('name' => 'NEW_ADMIN_EMAIL',
									 'content' => $userEmail)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-team-new-billing-admin',
														$email,
														$adminDisplayName, // User Display Name
														$mergeTags);
			echo "done\n";


			echo "Todo Cloud Autorenewal switching from stripe...";
			$mergeTags = array(
							   array('name' => 'FIRST_NAME',
									 'content' => $firstName),
							   array('name' => 'EXPIRATION_DATE',
									 'content' => $expirationDateString)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-autorenewal-notice-switching-from-stripe',
														$email,
														NULL, // User Display Name
														$mergeTags);
			echo "done\n";




			echo "Todo Cloud Referral Purchase Extension for IAP...";

			$mergeTags = array(
							   array('name' => 'FIRST_NAME',
									 'content' => $firstName),
							   array('name' => 'REFERRAL_STATUS_URL',
									 'content' => $referralStatusURL)
							   );
			$mergeTags[] = array('name' => 'AUTORENEWING_IAP_DETECTED',
								 'content' => 'true');

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-purchase-extension',
												 $email,
												 NULL, // User Display Name
												 $mergeTags);

			echo "done\n";

			echo "Todo Cloud Referral Purchase Extension Extended Account...";

			$mergeTags = array(
							   array('name' => 'FIRST_NAME',
									 'content' => $firstName),
							   array('name' => 'REFERRAL_STATUS_URL',
									 'content' => $referralStatusURL)
							   );
			$mergeTags[] = array('name' => 'EXTENDED_REFERRER_ACCOUNT',
								 'content' => 'true');
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-purchase-extension',
												 $email,
												 NULL, // User Display Name
												 $mergeTags);

			echo "done\n";


			echo "Todo Cloud Referral Purchase Extension Didn't Extend Account...";

			$mergeTags = array(
							   array('name' => 'FIRST_NAME',
									 'content' => $firstName),
							   array('name' => 'REFERRAL_STATUS_URL',
									 'content' => $referralStatusURL)
							   );
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-purchase-extension',
												 $email,
												 NULL, // User Display Name
												 $mergeTags);

			echo "done\n";




			echo "Todo Cloud Referral Purchase Extension Didn't Extend Account...";

			$mergeTags = array(
							   array('name' => 'FIRST_NAME',
									 'content' => $firstName),
							   array('name' => 'SENDER_DISPLAY_NAME',
									 'content' => $senderDisplayName),
							   array('name' => 'REFERRAL_LINK',
									 'content' => $referralLink),
							   array('name' => 'COPYRIGHT_YEAR',
									 'content' => $copyrightYear),
							   array('name' => 'REFERRALS_UNSUBSCRIBE_LINK',
									 'content' => $unsubscribeLink)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-referral-purchase-extension',
													 $email,
													 NULL, // User Display Name
												 $mergeTags);
			echo "done\n";



			echo "Todo Cloud Gift Code Purchase Receipt...";

			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $displayName),
							   array('name' => 'PAYMENT_DATE',
									 'content' => $paymentDate),
							   array('name' => 'PAYMENT_METHOD',
									 'content' => $paymentMethod),
							   array('name' => 'GIFT_CODE_ITEMS',
									 'content' => $giftCodeItems),
							   array('name' => 'PURCHASE_AMOUNT',
									 'content' => $purchaseAmount)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-purchase-receipt',
														$email,
														$displayName, // User Display Name
														$mergeTags);
			echo "done\n";




			echo "Todo Cloud Gift Code User Link...";
			$mergeTags = array(
							   array('name' => 'SENDER_DISPLAY_NAME',
									 'content' => $senderName),
							   array('name' => 'SENDER_MESSAGE',
									 'content' => $message),
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $recipientName),
							   array('name' => 'SITE_URL',
									 'content' => $siteURL),
							   array('name' => 'GIFT_CODE_LINK',
									 'content' => $giftCodeLink)
							   );

			TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-send-link',
														$email,
														$recipientName, // User Display Name
														$mergeTags);
			echo "done\n";




			echo "Todo Cloud Gift Code for Team Member...";
			$mergeTags = array(
							   array('name' => 'USER_DISPLAY_NAME',
									 'content' => $recipientName),
							   array('name' => 'SITE_URL',
									 'content' => $siteURL),
							   array('name' => 'GIFT_CODE_MONTHS',
									 'content' => $giftCodeMonths),
							   array('name' => 'GIFT_CODE_LINK',
									 'content' => $giftCodeLink)
							   );
			TDOMailer::sendMandrillEmailTemplate('todo-cloud-gift-code-for-team-member',
														$email,
														$recipientName, // User Display Name
														$mergeTags);
			echo "done\n";



			adminTool();
			return;

		}
		else if (trim($line) == "15")
		{
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			// Check to see if the user has any existing IAP receipt data
			$iapReceipt = TDOInAppPurchase::IAPAutorenewReceiptForUser($userid);
			$gpToken = TDOInAppPurchase::googlePlayTokenForUser($userid);

			if (empty($iapReceipt) && empty($gpToken))
			{
				echo "No IAP Receipt or Google Play Token available\n";
				adminTool();
				return;
			}

			$receiptInfo = $iapReceipt;
			if (empty($receiptInfo))
			{
				$receiptInfo = $gpToken;
			}

			$isAutorenewalCancelled = false;
			if ($receiptInfo['autorenewal_canceled'] == 1)
			{
				echo "** User's autorenewal IS cancelled. **\n";
				$isAutorenewalCancelled = true;
			}
			else
			{
				echo "** User's autorenewal is NOT cancelled. **\n";
			}

			echo "Continue and try processing an autorenewal for this user?. Type \"yes\" to proceed: ";
			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$response = strtolower(trim($line));
			if ($response != "yes")
			{
				echo "Aborting (you didn't type \"yes\")\n";
				adminTool();
				return;
			}

			if ($isAutorenewalCancelled == true)
			{
				// Set auto renew to NOT cancelled so that the autorenewal
				// process will be attempted.

				$link = TDOUtil::getDBLink();
				if (empty($link))
				{
					echo "Could not get a connection to the DB\n";
					adminTool();
					return;
				}

				$escapedUserId = mysql_real_escape_string($userid, $link);

				$sql = "";
				if (!empty($iapReceipt))
				{
					// This is Apple IAP User. Set auto renew receipt to NOT cancelled
					$sql = "UPDATE tdo_iap_autorenew_receipts SET autorenewal_canceled=0 WHERE userid='$escapedUserId'";
				}
				else
				{
					// This is Google Play
					$sql = "UPDATE tdo_googleplay_autorenew_tokens SET autorenewal_canceled=0 WHERE userid='$escapedUserId'";
				}

				$response = mysql_query($sql, $link);
				if (!$response)
				{
					TDOUtil::closeDBLink($link);
					echo "Error setting the autorenew to NOT cancelled\n";
					adminTool();
					return;
				}

				TDOUtil::closeDBLink($link);
			}

			$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userid);
			$result = TDOSubscription::processAutorenewalForSubscription($subscriptionID);
			if ($result)
			{
				echo "Successfully renewed the user account\n";

				// The way that processAutorenewalForSubscription() is implemented,
				// it will respond with success even if the subscription itself
				// has expired. Because of this, check to see if we should
				// mark the IAP as cancelled.

				$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
				if (empty($subscription))
				{
					echo "Unable to read subscription for user\n";
					adminTool();
					return;
				}

				$recordedExpirationDate = $subscription->getExpirationDate();
				$now = time();
				if ($recordedExpirationDate <= $now)
				{
					TDOInAppPurchase::markIAPAutorenewalCanceledForUser($userid);
					TDOInAppPurchase::markGooglePlayAutorenewalCanceledForUser($userid);

					echo "NOTE: This user's account has expired. Newest expiration date is: " . date(DateTime::ISO8601, $subscription->getExpirationDate()) . "\n";
				}
			}
			else
			{
				echo "Could not successfully renew the user account\n";
			}

			adminTool();
			return;
		}
		else if (trim($line) == "16")
		{
			$pageSize = 10;
			$pageOffset = 0;

			do
			{
				$teams = TDOTeamAccount::getAllTeams($pageOffset, $pageSize);
				foreach ($teams as $team)
				{
					showTeamInfo($team);
				}

				$pageOffset++;
			} while (($teams !== false) && (count($teams) >= $pageSize));

			adminTool();
			return;
		}
		else if (trim($line) == "17")
		{
			echo "1. EVERYONE (opted-in AND opted-out)\n";
			echo "2. Opt-in only\n";
			echo "\n\n";
			echo "Select an option and press <ENTER> (empty to go back to main menu): ";

			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				//echo "Empty response\n";
				adminTool();
				return;
			}

			$optInOnly = false;
			$exportFileName = "./todo-cloud-user-emails-everyone.csv";

			if (trim($line) == "1") {
				echo "Exporting ALL emails...\n";
			} else if (trim($line) == "2") {
				echo "Exporting ONLY opted-in emails...\n";
				$exportFileName = "./todo-cloud-user-emails-opted-in.csv";
				$optInOnly = true;
			} else {
				echo "Unknown option. Returning to main menu...\n";
				adminTool();
				return;
			}

			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			$sql = "SELECT username FROM tdo_user_accounts";
			if ($optInOnly) {
				$sql .= " WHERE email_opt_out = 0 ORDER BY username";
			}
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				echo "No users";
				TDOUtil::closeDBLink($link);
				adminTool();
				return;
			}

			$outputFile = fopen($exportFileName, "w");
			if (!$outputFile)
			{
				echo "Could not create output file\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			while ($row = mysql_fetch_array($result))
			{
				fwrite($outputFile, $row[0] . "\n");
			}

			fclose($outputFile);

			echo "Successfully exported: " . $exportFileName . "\n";

			TDOUtil::closeDBLink($link);

			adminTool();
			return;
		}
		else if (trim($line) == "18")
		{
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			// Set up the analysis tables
			mysql_query("DROP TABLE IF EXISTS admin_analyze_lists", $link);
			mysql_query("DROP TABLE IF EXISTS admin_analyze_tasks", $link);
			mysql_query("DROP TABLE IF EXISTS admin_analyze_projects", $link);
			mysql_query("DROP TABLE IF EXISTS admin_analyze_checklists", $link);

			if (!mysql_query("CREATE TABLE admin_analyze_lists (name varchar(510) NOT NULL UNIQUE, count int(11) DEFAULT 0, KEY admin_analyze_list_name_key(name(10)))", $link))
			{
				echo "Could not create the admin_analyze_lists table.\n";
				TDOUtil::closeDBLink($link);
				adminTool();
			}

			if (!mysql_query("CREATE TABLE admin_analyze_tasks (name varchar(510) NOT NULL UNIQUE, count int(11) DEFAULT 0, KEY admin_analyze_task_name_key(name(10)))", $link))
			{
				echo "Could not create the admin_analyze_tasks table.\n";
				TDOUtil::closeDBLink($link);
				adminTool();
			}

			if (!mysql_query("CREATE TABLE admin_analyze_projects (name varchar(510) NOT NULL UNIQUE, count int(11) DEFAULT 0, KEY admin_analyze_project_name_key(name(10)))", $link))
			{
				echo "Could not create the admin_analyze_projects table.\n";
				TDOUtil::closeDBLink($link);
				adminTool();
			}

			if (!mysql_query("CREATE TABLE admin_analyze_checklists (name varchar(510) NOT NULL UNIQUE, count int(11) DEFAULT 0, KEY admin_analyze_checklist_name_key(name(10)))", $link))
			{
				echo "Could not create the admin_analyze_checklists table.\n";
				TDOUtil::closeDBLink($link);
				adminTool();
			}



			$sql = "SELECT userid FROM tdo_user_devices WHERE timestamp > (UNIX_TIMESTAMP() - (86400 * 7))";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				echo "No active users.\n";
				TDOUtil::closeDBLink($link);
				adminTool();
				return;
			}

			while ($row = mysql_fetch_array($result))
			{
				$userID = $row[0];
				$userActiveTaskCount = TDOTask::getTaskCountForUser($userID, false); // Don't include completed tasks
				if ($userActiveTaskCount < 25)
				{
					// Skip users that have less than 25 active tasks
					continue;
				}

				echo "Processing User ID: $userID\n";

				$now = time();
				$lastMonthTimestamp = $now - (86400 * 31);

				$listIDs = TDOList::getListIDsForUser($userID, $link);
				foreach ($listIDs as $listID)
				{
					$limit = 50;
					$offset = 0;

					$listName = TDOList::nameForListId($listID);
					$lowerCaseListName = strtolower($listName);
					incrementAnalysisItemCount($lowerCaseListName, "admin_analyze_lists", $link);
					echo "    List ($listID): $lowerCaseListName\n";

					while ($results = TDOTask::getActiveTasksForUserModifiedSince($userID, $lastMonthTimestamp, $listID, $offset, $limit, false, $link))
					{
						if (empty($results))
						{
							break;
						}
						$tasksArray = $results['tasks'];
						if (empty($tasksArray))
						{
							break;
						}

						$numOfTasks = count($tasksArray);
						if ($numOfTasks == 0)
						{
							break;
						}

						foreach ($tasksArray as $task)
						{
							$taskName = $task->name();
							$lowerCaseName = strtolower($taskName);

							$analysisTableName = "admin_analyze_tasks";
							if ($task->isProject())
							{
								echo "        project: $lowerCaseName\n";
								$analysisTableName = "admin_analyze_projects";
							}
							else if ($task->isChecklist())
							{
								$analysisTableName = "admin_analyze_checklists";
								echo "        checklist: $lowerCaseName\n";
							}
							else
							{
								echo "        task: $lowerCaseName\n";
							}

							incrementAnalysisItemCount($lowerCaseName, $analysisTableName, $link);
						}

						$offset = $offset + $numOfTasks;
					}

				}
			}

			TDOUtil::closeDBLink($link);
			adminTool();
			return;
		}
		else if (trim($line) == "19")
		{
			$mandrillTemplateID = promptForItem("Mandrill Template ID");
			if (empty($mandrillTemplateID))
			{
				echo "Empty Mandrill template ID\n";
				adminTool();
				return;
			}

			$startTimestamp = promptForItem("Start Date (UNIX Timestamp)");
			if (empty($startTimestamp))
			{
				echo "Empty timestamp\n";
				adminTool();
				return;
			}
			$endTimestamp = promptForItem("End Date (UNIX Timestamp)");
			if (empty($endTimestamp))
			{
				echo "Empty timestamp\n";
				adminTool();
				return;
			}

                        $link = TDOUtil::getDBLink();
                        if (!$link)
                        {
                                echo "Could not get a connection to the database. Exiting...\n";
                                exit(1);
                        }

			$users = getOptedInUsersWithDateRange($startTimestamp, $endTimestamp, $link);

			if (empty($users))
			{
				echo "Empty list of users.\n";
				adminTool();
			}

			$outputFile = fopen("./onboard-mailer.log", "w");
			if (!$outputFile)
			{
				echo "Could not create output file\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			fwrite($outputFile, "Mandrill Template ID: $mandrillTemplateID\n");
			fwrite($outputFile, "          Start Date: " . date(DateTime::ISO8601, $startTimestamp) . "\n");
			fwrite($outputFile, "            End Date: " . date(DateTime::ISO8601, $endTimestamp) . "\n");

			echo "Found " . count($users) . " users to send emails to.\n";
			fwrite($outputFile, "Found " . count($users) . " users to send emails to.\n");
			$numOfEmailsSent = 0;
			$processCount = 0;

			foreach ($users as $user)
			{
				$processCount++;
				$userID = $user->userId();
				$username = $user->username();
				$displayName = $user->firstName();
				$preHash = '86104B2D-DC10-4538-9A0E-61E974565D5E' . $username . $userID . '86104B2D-DC10-4538-9A0E-61E974565D5E' . "47";
				$optOutKey = md5($preHash);
				$optOutLink = "https://www.todo-cloud.com/?optOutEmails=true&email=$username&optOutKey=$optOutKey";
				$mergeTags[] = array('name' => "OPT_OUT_LINK", 'content' => $optOutLink);

				$result = TDOMailer::sendMandrillEmailTemplate($mandrillTemplateID,
										$username,
										$displayName,
										$mergeTags);

				if ($result)
				{
					echo "$processCount: Sent $mandrillTemplateID to $username\n";
					fwrite($outputFile, "$processCount: Sent $mandrillTemplateID to $username\n");
					$numOfEmailsSent++;
				}
				else
				{
					echo "$processCount: Failed to send $mandrillTemplateID to $username\n";
					fwrite($outputFile, "$processCount: Failed to send $mandrillTemplateID to $username\n");
				}
			}

			echo "Sent $numOfEmailsSent emails (Mandrill Template ID: $mandrillTemplateID)\n";
			fwrite($outputFile, "Sent $numOfEmailsSent emails (Mandrill Template ID: $mandrillTemplateID)\n");

			fclose($outputFile);
			TDOUtil::closeDBLink($link);
			adminTool();
			return;
		}
		else if (trim($line) == "20")
		{
			$outputFile = fopen("./todo-cloud-teams.csv", "w");
			if (!$outputFile)
			{
				echo "Could not create output file\n";
				exit(1);
			}

			$pageSize = 10;
			$pageOffset = 0;
			$printHeaderRow = true;

			do
			{
				$teams = TDOTeamAccount::getAllTeams($pageOffset, $pageSize);
				foreach ($teams as $team)
				{
					printTeamInfoToFile($team, $outputFile, $printHeaderRow);
					$printHeaderRow = false;
				}

				$pageOffset++;
			} while (($teams !== false) && (count($teams) >= $pageSize));

			fclose($outputFile);

			adminTool();
			return;

		}
		else if (trim($line) == "21")
		{
			// Steps to archive completed tasks for a given user's list
			//	1.	prompt for the username
			//	2.	print the user's lists (with line numbers)
			//	3.	prompt for the list number to archive
			//	4.	prompt for the archival date
			//	5.	confirm all the parameters and perform the archive
			$username = promptAndGetUsername();
			if (empty($username))
			{
				echo "Empty username\n";
				adminTool();
				return;
			}
			$userid = TDOUser::userIdForUserName($username);
			if (empty($userid))
			{
				echo "User not found\n";
				adminTool();
				return;
			}

			$lists = showUserLists($userid, false, true);

			if (!$lists || empty($lists) || count($lists) == 0)
			{
				echo "No active lists found for user\n";
				adminTool();
				return;
			}

			// Prompt the user for the list they wish to restore
			echo "\nEnter the index of the list you wish to archive completed tasks in: ";
			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$listIndex = trim($line);

			if (!isset($lists[$listIndex]))
			{
				echo "You specified an invalid list index.\n";
				adminTool();
				return;
			}

			$list = $lists[$listIndex];

			// Prompt for the archive date
			$archiveDate = promptForItem("Archive Date (YYYY-MM-DD)");
			if (empty($archiveDate))
			{
				echo "Empty date\n";
				adminTool();
				return;
			}

			$archiveTimestamp = strtotime($archiveDate);

			echo "You've specified the list \"" . $list->name() . "\" (" . $list->listId() . ") with an archive date of " . date('c', $archiveTimestamp) . ". Type \"yes\" to proceed: ";

			$line = fgets(STDIN);
			if (!$line || empty($line) || strlen(trim($line)) == 0)
			{
				echo "Empty response\n";
				adminTool();
				return;
			}

			$response = strtolower(trim($line));
			if ($response != "yes")
			{
				echo "Aborting (you didn't type \"yes\")\n";
				adminTool();
				return;
			}

			// Check to see if this user is an owner of this list
			$userRole = TDOList::getRoleForUser($list->listId(), $userid);

			if ($userRole != LIST_MEMBERSHIP_OWNER && $userRole != LIST_MEMBERSHIP_MEMBER)
			{
				echo "This user is not an owner or member of the specified list.\n";
				adminTool();
				return;
			}

			// Do all of this in a transaction so a full restore happens or nothing at all
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "Could not get a connection to the database. Exiting...\n";
				exit(1);
			}

			if (!mysql_query("START TRANSACTION", $link))
			{
				echo "Could not start a DB transaction. Exiting...\n";
				TDOUtil::closeDBLink($link);
				exit(1);
			}

			$moreTasks = true;
			$offset = 0;
			$limit = 50;
			$numOfTasksArchived = 0;
			do {
				$numOfTasksArchivedInsideLoop = 0;
				$results = TDOTask::getCompletedTasksForUserModifiedSince($userid, 0, $list->listId(), $offset, $limit, $link);
				if (isset($results))
				{
					$tasks = $results['tasks'];

					if(isset($tasks) && count($tasks) > 0) {
						echo "Processing " . count($tasks) . " tasks...\n";

						foreach ($tasks as $task) {
							$completionDate = $task->completionDate();
							if ($completionDate > $archiveTimestamp) {
								// Skip this task because the completion date is later than the
								// archive date.
								continue;
							}

							$taskID = $task->taskId();

							if (TDOTask::archiveObject($taskID, $link, true) == false) {
								echo "Error archiving $taskID. Rolling back the transaction and exiting...\n";
								mysql_query("ROLLBACK", $link);
								TDOUtil::closeDBLink($link);
								adminTool();
								return;
							}

							$numOfTasksArchived++;
							$numOfTasksArchivedInsideLoop++;
						}
					} else {
						$moreTasks = false;
						break;
					}
				}
				else
				{
					$moreTasks = false;
					break;
				}

				// Don't adjust the offset because we're archiving the oldest tasks and
				// as we keep calling getCompletedTasksForUserModifiedSince(), we'll
				// be getting a new set of tasks to operate on.

				if ($numOfTasksArchivedInsideLoop == 0) {
					// Time to quit, we must have reached a point in the data where the
					// completed tasks are newer than our archive date
					$moreTasks = false;
					break;
				}
			} while ($moreTasks);

			if (!mysql_query("COMMIT", $link))
			{
				echo "Couldn't commit the transaction to archive tasks in the list: " . $list->listId() . " Error: " . mysql_error() . "\n";
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				echo "Rollback completed. Restarting...\n\n";
				adminTool();
				return;
			}

			TDOUtil::closeDBLink($link);

			echo "Successfully archived $numOfTasksArchived tasks in the list \"" . $list->name() . "\" (" . $list->listId() . ").\n\n";
		}
		else
		{
			echo "Unknown option\n";
			adminTool();
			return;
		}

		adminTool(); // Start over after success

	}


	// Launch the app
	adminTool();
	/*
	 $link = TDOUtil::getDBLink();
	 if (!$link)
	 {
	 echo "Could not get a connection to the database.\n";
	 exit(3);
	 }
	 */

	//TDOUtil::closeDBLink($link);

?>

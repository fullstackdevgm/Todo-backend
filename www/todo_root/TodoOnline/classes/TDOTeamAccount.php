<?php
	//      TDOTeamAccount
	//      Used to manage and access team accounts
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/DBConstants.php');
	include_once('Facebook/config.php');
	include_once('Facebook/facebook.php');
	include_once('Stripe/Stripe.php');
	
	Stripe::setApiKey(APPIGO_STRIPE_SECRET_KEY);
	
	
	define('TEAM_ACCOUNTS_DB_TABLE_COLUMNS', "teamid, teamname, license_count, billing_userid, expiration_date, creation_date, modified_date, billing_frequency, new_license_count, biz_name, biz_phone, biz_addr1, biz_addr2, biz_city, biz_state, biz_country, biz_postal_code, main_listid, discovery_answer");
	// prevent more than 100 accounts from being returned at a time
	define('TEAM_ACCOUNTS_LIST_MAX_RETURN_LIMIT', 100);
	
	//
	// Membership Types
	//
	define ('TEAM_MEMBERSHIP_TYPE_MEMBER', 0);
	define ('TEAM_MEMBERSHIP_TYPE_ADMIN', 1);
	
	//
	// Autorenew Defines
	//
	define('TEAM_SUBSCRIPTION_RETRY_MAX_ATTEMPTS', 3);
	define('TEAM_SUBSCRIPTION_RENEW_LEAD_TIME',60*60*24);	// One day
	
	
	
	class TDOTeamAccount
	{
		/*
		 teamid VARCHAR(36) NOT NULL,
		 teamname VARCHAR(128) NOT NULL,
		 license_count INT NOT NULL DEFAULT 0,
		 billing_userid VARCHAR(36) NOT NULL,
		 expiration_date INT NOT NULL DEFAULT 0,
		 creation_date INT NOT NULL DEFAULT 0,
		 modified_date INT NOT NULL DEFAULT 0,
		 billing_frequency TINYINT(1) NOT NULL DEFAULT 0,
		 new_license_count INT NOT NULL DEFAULT 0,
		 main_listid VARCHAR(36) NOT NULL,
		 */
		
		private $_teamID;
		private $_teamName;
        private $_licenseCount;
		private $_billingUserID;
		private $_expirationDate;
		private $_creationDate;
        private $_modifiedDate;
		private $_billingFrequency; // 1 monthly, 2 yearly
		private $_newLicenseCount;
		private $_bizName;
		private $_bizPhone;
		private $_bizAddr1;
		private $_bizAddr2;
		private $_bizCity;
		private $_bizState;
		private $_bizCountry;
		private $_bizPostalCode;
		private $_mainListID;
		private $_discoveryAnswer;
		
		
		public function __construct()
		{
			$this->set_to_default();      
		}
		
		
		public function set_to_default()
		{
			// clears values without going to database
			$this->_teamID = NULL;
			$this->_teamName = NULL;
			$this->_licenseCount = 0;
			$this->_billingUserID = NULL;
            $this->_expirationDate = time();
			$this->_creationDate = $this->_expirationDate;
			$this->_modifiedDate = $this->_creationDate;
			$this->_billingFrequency = 0;
            $this->_newLicenseCount = 0;
			$this->_bizName = NULL;
			$this->_bizPhone = NULL;
			$this->_bizAddr1 = NULL;
			$this->_bizAddr2 = NULL;
			$this->_bizCity = NULL;
			$this->_bizState = NULL;
			$this->_bizCountry = NULL;
			$this->_bizPostalCode = NULL;
			$this->_mainListID = NULL;
			$this->_discoveryAnswer = NULL;
		}
		
		
		//
		// Class Getters/Setters
		//
        
		
		public function getTeamID()
		{
			return $this->_teamID;
		}
		public function setTeamID($val)
		{
			$this->_teamID = $val;
		}
		
		public function getTeamName()
		{
			return $this->_teamName;
		}
		public function setTeamName($val)
		{
			$this->_teamName = $val;
		}
		
		public function getLicenseCount()
		{
			return $this->_licenseCount;
		}
		public function setLicenseCount($val)
		{
			$this->_licenseCount = $val;
		}
		
        public function getBillingUserID()
		{
			return $this->_billingUserID;
		}
		public function setBillingUserID($val)
		{
			$this->_billingUserID = $val;
		}
		
		public function getExpirationDate()
		{
			return $this->_expirationDate;
		}
		public function setExpirationDate($val)
		{
			$this->_expirationDate = $val;
		}
		
		public function getCreationDate()
		{
			return $this->_creationDate;
		}
		public function setCreationDate($val)
		{
			$this->_creationDate = $val;
		}
        
        public function getModifiedDate()
		{
			return $this->_modifiedDate;
		}
		public function setModifiedDate($val)
		{
			$this->_modifiedDate = $val;
		}
		
        public function getBillingFrequency()
		{
			return $this->_billingFrequency;
		}
		public function setBillingFrequency($val)
		{
			$this->_billingFrequency = $val;
		}
		
        public function getNewLicenseCount()
		{
			return $this->_newLicenseCount;
		}
		public function setNewLicenseCount($val)
		{
			$this->_newLicenseCount = $val;
		}
		
		public function getBizName()
		{
			return $this->_bizName;
		}
		public function setBizName($val)
		{
			$this->_bizName = $val;
		}
		
		public function getBizPhone()
		{
			return $this->_bizPhone;
		}
		public function setBizPhone($val)
		{
			$this->_bizPhone = $val;
		}
		
		public function getBizAddr1()
		{
			return $this->_bizAddr1;
		}
		public function setBizAddr1($val)
		{
			$this->_bizAddr1 = $val;
		}
		
		public function getBizAddr2()
		{
			return $this->_bizAddr2;
		}
		public function setBizAddr2($val)
		{
			$this->_bizAddr2 = $val;
		}
		
		public function getBizCity()
		{
			return $this->_bizCity;
		}
		public function setBizCity($val)
		{
			$this->_bizCity = $val;
		}
		
		public function getBizState()
		{
			return $this->_bizState;
		}
		public function setBizState($val)
		{
			$this->_bizState = $val;
		}
		
		public function getBizCountry()
		{
			return $this->_bizCountry;
		}
		public function setBizCountry($val)
		{
			$this->_bizCountry = $val;
		}
		
		public function getBizPostalCode()
		{
			return $this->_bizPostalCode;
		}
		public function setBizPostalCode($val)
		{
			$this->_bizPostalCode = $val;
		}
		
		public function getMainListID()
		{
			return $this->_mainListID;
		}
		public function setMainListID($val)
		{
			$this->_mainListID = $val;
		}
		
		public function getDiscoveryAnswer()
		{
			return $this->_discoveryAnswer;
		}
		public function setDiscoveryAnswer($val)
		{
			$this->_discoveryAnswer = $val;
		}
		
		
		public function getPropertiesArray()
		{
			$billingDisplayName = TDOUser::displayNameForUserId($this->getBillingUserID());
			$billingUsername = TDOUser::usernameForUserId($this->getBillingUserID());
			$bizCountryName = TDOTeamAccount::countryNameForCode($this->getBizCountry());
			
			$properties = array(
								"teamid" => $this->getTeamID(),
								"teamName" => $this->getTeamName(),
								"licenseCount" => $this->getLicenseCount(),
								"billingUserID" => $this->getBillingUserID(),
								"billingDisplayName" => $billingDisplayName,
								"billingUsername" => $billingUsername,
								"expirationDate" => $this->getExpirationDate(),
								"creationDate" => $this->getCreationDate(),
								"modifiedDate" => $this->getModifiedDate(),
								"billingFrequency" => $this->getBillingFrequency(),
								"newLicenseCount" => $this->getNewLicenseCount(),
								"bizName" => $this->getBizName(),
								"bizPhone" => $this->getBizPhone(),
								"bizAddr1" => $this->getBizAddr1(),
								"bizAddr2" => $this->getBizAddr2(),
								"bizCity" => $this->getBizCity(),
								"bizState" => $this->getBizState(),
								"bizCountryCode" => $this->getBizCountry(),
								"bizCountryName" => $bizCountryName,
								"bizPostalCode" => $this->getBizPostalCode(),
								"mainListID" => $this->getMainListID(),
								"discoveryAnswer" => $this->getDiscoveryAnswer()
								);
			
			return $properties;
		}
		
		
		
		public static function teamFromRow($row)
		{
			if (empty($row))
				return false;
			
			if (isset($row['teamid']))
			{
				$account = new TDOTeamAccount();
				$account->setTeamID($row['teamid']);
				
				if (isset($row['teamname']))
					$account->setTeamName($row['teamname']);
				if (isset($row['license_count']))
					$account->setLicenseCount($row['license_count']);
				if (isset($row['billing_userid']))
					$account->setBillingUserID($row['billing_userid']);
				if (isset($row['expiration_date']))
					$account->setExpirationDate($row['expiration_date']);
				if (isset($row['creation_date']))
					$account->setCreationDate($row['creation_date']);
				if (isset($row['modified_date']))
					$account->setModifiedDate($row['modified_date']);
				if (isset($row['billing_frequency']))
					$account->setBillingFrequency($row['billing_frequency']);
				if (isset($row['new_license_count']))
					$account->setNewLicenseCount($row['new_license_count']);
				if (isset($row['biz_name']))
					$account->setBizName($row['biz_name']);
				if (isset($row['biz_phone']))
					$account->setBizPhone($row['biz_phone']);
				if (isset($row['biz_addr1']))
					$account->setBizAddr1($row['biz_addr1']);
				if (isset($row['biz_addr2']))
					$account->setBizAddr2($row['biz_addr2']);
				if (isset($row['biz_city']))
					$account->setBizCity($row['biz_city']);
				if (isset($row['biz_state']))
					$account->setBizState($row['biz_state']);
				if (isset($row['biz_country']))
					$account->setBizCountry($row['biz_country']);
				if (isset($row['biz_postal_code']))
					$account->setBizPostalCode($row['biz_postal_code']);
				if (isset($row['main_listid']))
					$account->setMainListID($row['main_listid']);
				if (isset($row['discovery_answer']))
					$account->setDiscoveryAnswer($row['discovery_answer']);
				
				return $account;
			}
			
			return false;
		}
		
		
		public static function getTeamForTeamID($teamID, $requiredUserID=NULL, $link=NULL)
		{
			if (!isset($teamID))
				return false;
			
			// NOTE: If a $requiredUserID is specified, we need to verify that
			// this user is either an administrator or member of the team. If
			// they are not, they do not have authorization to view the team
			// information. This should only be left as NULL from the admin
			// interfaces.
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamForTeamID() could not get DB connection.");
					return false;
				}
			}
			
			
			$teamID = mysql_real_escape_string($teamID, $link);
			if (isset($requiredUserID))
			{
				$requiredUserID = mysql_real_escape_string($requiredUserID);
				$sql = "SELECT * FROM tdo_team_accounts WHERE teamid='$teamID' AND teamid IN (SELECT T1.teamid FROM tdo_team_admins AS T1 LEFT JOIN tdo_team_members AS T2 ON T1.teamid=T2.teamid WHERE (T1.userid='$requiredUserID' OR T2.userid='$requiredUserID'))";
			}
			else
			{
				$sql = "SELECT * FROM tdo_team_accounts WHERE teamid='$teamID'";
			}
			
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['teamid']))
				{
					$team = TDOTeamAccount::teamFromRow($row);
					if ($team)
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						
						return $team;
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamForTeamID() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function removeBillingUserFromTeam($teamID, $link=NULL)
		{
			if (!isset($teamID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::removeBillingUserFromTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "UPDATE tdo_team_accounts SET billing_userid=NULL WHERE teamid='$teamID'";
			
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::removeBillingUserFromTeam() unable to remove billing user for team ($teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Email the admins of the team to let them know that no billing
			// administrator exists.
			$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamID, $link);
			if (!$teamAdminIDs)
			{
				// Should still return true since the billing admin was actually removed
				error_log("TDOTeamAccount::removeBillingUserFromTeam() unable to determine the list of team administrators.");
			}
			
			$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
			
			foreach($teamAdminIDs as $teamAdminUserID)
			{
				$adminEmail = TDOUser::usernameForUserId($teamAdminUserID);
				$adminDisplayName = TDOUser::displayNameForUserId($teamAdminUserID);
				
				TDOMailer::sendTeamRemovedBillingAdminNotification($teamID, $teamName, $adminEmail, $adminDisplayName);
			}
			
			return true;
		}
		
		
		public static function setBillingUserForTeam($teamID, $billingUserID, $link=NULL)
		{
			if (!isset($teamID))
				return false;
			if (!isset($billingUserID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::setBillingUserForTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$billingUserID = mysql_real_escape_string($billingUserID, $link);
			$sql = "UPDATE tdo_team_accounts SET billing_userid='$billingUserID' WHERE teamid='$teamID'";
			
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::setBillingUserForTeam() unable to set billing user for team ($teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Any time we successfully set billing information for a team, we
			// should make sure that the team isn't stuck in the auto-renew
			// table.
			TDOTeamAccount::removeTeamFromAutorenewQueue($teamID);
			
			return true;
		}
		
		
		public static function deleteTeamAccount($teamID, $link = NULL)
		{
			if (!isset($teamID))
				return array("success" => false, "error" => "Missing parameter: teamID.");
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::deleteTeamAccount() could not get DB connection.");
					return array("success" => false, "error" => "Could not connect to database. Please try again.");
				}
			}
			
			// ONLY allow a team account to be deleted if there are no members
			// of the team.
			$numberOfTeamMembers = TDOTeamAccount::getCurrentTeamMemberCount($teamID, TEAM_MEMBERSHIP_TYPE_MEMBER, $link);
			if ($numberOfTeamMembers === false)
			{
				error_log("TDOTeamAccount::deleteTeamAccount() unable to determine how many members of the team exist.");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Failed to determine the number of team members prior to deletion. Please try again.");
			}
			
			if ($numberOfTeamMembers > 0)
			{
				error_log("TDOTeamAccount::deleteTeamAccount() cannot delete a team account that has members. All members must be removed before deletion.");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Cannot delete a team with assigned members. Remove all members and try again.");
			}
			
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::deleteTeamAccount() couldn't start a transaction: " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "The database is busy. Please try again later.");
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "DELETE FROM tdo_team_accounts WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::deleteTeamAccount() unable to delete team ($teamID): " . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Could not delete the team. Please try again later.");
			}
			
			// Delete all administrators
			$sql = "DELETE FROM tdo_team_admins WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::deleteTeamAccount() unable to delete admins for team ($teamID): " . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Could not remove the team administrators. Please try again later.");
			}
			
			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::deleteTeamAccount() couldn't commit transaction after deleting team ($teamID)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Could not finalize the team deletion. Please try again later.");
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return array("success" => true);
		}
		
		
		public static function getTeamsCount($link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamsCount() could not get DB connection.");
					return false;
				}
			}
			
			$sql = "SELECT COUNT(*) FROM tdo_team_accounts";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamsCount(): Unable to get total number of teams");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		public static function getTeamPurchasedUsersCount($link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamPurchasedUsersCount() could not get DB connection.");
					return false;
				}
			}
			
			$sql = "SELECT SUM(license_count) FROM tdo_team_accounts";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamPurchasedUsersCount(): Unable to get total number of purchased team users");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return 0;
		}
		
		
		public static function getTeamUsersInUseCount($link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamUsersInUseCount() could not get DB connection.");
					return false;
				}
			}
			
			$sql = "SELECT COUNT(*) FROM tdo_subscriptions WHERE teamid IS NOT NULL AND teamid != ''";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamUsersInUseCount(): Unable to get total number of in-use team licenses");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return 0;
		}
		
		
		public static function getCurrentTeamMemberCount($teamID, $membershipType=TEAM_MEMBERSHIP_TYPE_MEMBER, $link = NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getCurrentTeamMemberCount() passed empty teamID");
				return false;
			}
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getCurrentTeamMemberCount() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$tableName = "tdo_team_members";
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
				$tableName = "tdo_team_admins";
			
			$sql = "SELECT COUNT(*) FROM $tableName WHERE teamid='$teamID'";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getCurrentTeamMemberCount($teamID): Unable to get team member count");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function getTeamLicensesPurchaseCountInRange($billingFrequency=SUBSCRIPTION_TYPE_MONTH, $startDate=0, $endDate=0, $link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamLicensesPurchaseCountInRange() could not get DB connection.");
					return 0;
				}
			}
			
			$sql = "SELECT SUM(license_count) FROM tdo_stripe_payment_history WHERE type=$billingFrequency AND timestamp >= $startDate AND timestamp <= $endDate AND (teamid IS NOT NULL OR teamid !='')";
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamLicensesPurchaseCountInRange($startDate, $endDate): Unable to get team user count");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return 0;
		}
		
		
		public static function getTeamsForSearchString($searchString, $limit, $offset, $link=NULL)
		{
			if(empty($searchString))
				return false;
			
			$searchArray = preg_split('/\s+/', $searchString);
			if(empty($searchArray))
				return false;
            
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamsForSearchString() could not get DB connection.");
					return false;
				}
			}
			
			$searchString = mysql_real_escape_string($searchString, $link);
			$limit = intval($limit);
			$offset = intval($offset);
			
			$sql = "SELECT * FROM tdo_team_accounts WHERE ";
			
			$whereStatement = "";
			foreach($searchArray as $searchItem)
			{
				if(strlen($searchItem) == 0)
					continue;
				
				if(strlen($whereStatement) > 0)
				{
					$whereStatement .= " AND";
				}
				$whereStatement .= " (teamname LIKE '%".$searchItem."%' OR biz_name LIKE '%".$searchItem."%')";
			}
			
			if(strlen($whereStatement) == 0)
			{
				return false;
			}
			
			$sql .= $whereStatement;
			$sql .= " ORDER BY teamname LIMIT $limit OFFSET $offset";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				$teams = array();
				while($row = mysql_fetch_array($result))
				{
					$team = TDOTeamAccount::teamFromRow($row);
					$teams[] = $team;
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return $teams;
			}
			else
				error_log("TDOTeamAccount::getTeamsForSearchString() failed: ".mysql_error());
            
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function getTeamMemberSubscriptionIDs($teamID, $link = NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getTeamMemberSubscriptionIDs() passed empty teamID");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamMemberSubscriptionIDs() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT subscriptionid FROM tdo_subscriptions WHERE teamid='$teamID'";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				$subscriptions = array();
				while ($row = mysql_fetch_array($result))
				{
					if (isset($row['subscriptionid']))
						$subscriptions[] = $row['subscriptionid'];
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $subscriptions;
			}
			else
			{
				error_log("TDOTeamAccount::getTeamMemberSubscriptionIDs($teamID): Unable to get subscriptionids for team members: " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public function addAccount($link=NULL)
		{
			// Required items
			//	billingUserID (the creator initially)
			
			if ($this->_billingUserID == NULL)
			{
				error_log("TDOTeamAccount::addAccount() failed because the billingUserID (the original creator of the team) was not set.");
				return false;
			}
			
			$closeLink = false;
            if(empty($link))
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if(!$link)
                {   
                    error_log("TDOTeamAccount::addAccount() unable to get a DB link");
                    return false;               
                }
            }
			
			// We allow a single account to admininster multiple team accounts,
			// so don't bother checking for duplicate teams. We also allow a
			// team admin to not be an actual member (consume a license) of a
			// team.
            
            if($this->_teamID == NULL)
                $this->_teamID = TDOUtil::uuid();
			
			if ($this->_teamName == NULL)
				$this->_teamName = "Untitled Team";
			
			$teamID = mysql_real_escape_string($this->_teamID, $link);
			$teamName = mysql_real_escape_string($this->_teamName, $link);
			$creatorID = mysql_real_escape_string($this->_billingUserID, $link);
			
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::addAccount() couldn't start a transaction: " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
//error_log("BILLING_FREQUENCY_ON_ADD: " . $this->_billingFrequency);
			
			$bizName = NULL;
			if ($this->_bizName != NULL)
				$bizName = mysql_real_escape_string($this->_bizName);
			
			$bizPhone = NULL;
			if ($this->_bizPhone != NULL)
				$bizPhone = mysql_real_escape_string($this->_bizPhone);
			
			$bizAddr1 = NULL;
			if ($this->_bizAddr1 != NULL)
				$bizAddr1 = mysql_real_escape_string($this->_bizAddr1);
			
			$bizAddr2 = NULL;
			if ($this->_bizAddr2 != NULL)
				$bizAddr2 = mysql_real_escape_string($this->_bizAddr2);
			
			$bizCity = NULL;
			if ($this->_bizCity != NULL)
				$bizCity = mysql_real_escape_string($this->_bizCity);
			
			$bizState = NULL;
			if ($this->_bizState != NULL)
				$bizState = mysql_real_escape_string($this->_bizState);
			
			$bizCountry = NULL;
			if ($this->_bizCountry != NULL)
				$bizCountry = mysql_real_escape_string($this->_bizCountry);
			
			$bizPostalCode = NULL;
			if ($this->_bizPostalCode != NULL)
				$bizPostalCode = mysql_real_escape_string($this->_bizPostalCode);
			
			$mainListID = NULL;
			if ($this->_mainListID != NULL)
				$mainListID = mysql_real_escape_string($this->_mainListID);
			
			$discoveryAnswer = NULL;
			if ($this->_discoveryAnswer != NULL)
				$discoveryAnswer = mysql_real_escape_string($this->_discoveryAnswer);
			

			// "teamid, teamname, license_count, billing_userid, expiration_date, expiration_date, creation_date, modified_date, billing_frequency, new_license_count"
			$sql = "INSERT INTO tdo_team_accounts (" . TEAM_ACCOUNTS_DB_TABLE_COLUMNS . ") VALUES ('" . $teamID . "', '" . $teamName . "', " . $this->_licenseCount . ", '" . $creatorID . "', " . $this->_expirationDate . ", " . $this->_creationDate . ", " . $this->_modifiedDate . ", " . $this->_billingFrequency . ", " . $this->_newLicenseCount . ", '$bizName', '$bizPhone', '$bizAddr1', '$bizAddr2', '$bizCity', '$bizState', '$bizCountry', '$bizPostalCode', '$mainListID', '$discoveryAnswer')";
//error_log("SQL: " . $sql);
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				error_log("TDOTeamAccount::addAccount() couldn't insert a new record into the tdo_team_accounts table: " . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Now add in the original administrator of this team
			$sql = "INSERT INTO tdo_team_admins (teamid, userid) VALUES ('$this->_teamID', '$creatorID')";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				// We have a new unique key constraint that prevents an admin
				// from being added more than once. If we hit this problem, just
				// ignore the error.
				if (mysql_errno($link) != 1062) // duplicate key (already exist in table)
				{
					error_log("TDOTeamAccount::addAccount() couldn't insert a new admin record into the tdo_team_admins table: " . mysql_error());
					mysql_query("ROLLBACK", $link);
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::addAccount() couldn't commit transaction while attempting to create a new team ($this->_teamID)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		public static function createTeamAccountWithAdmin($adminUserID, $teamName=NULL, $link=NULL)
		{
			if (!isset($adminUserID))
				return false;
			
			$account = new TDOTeamAccount();
			$account->setBillingUserID($adminUserID);
			
			if (!empty($teamName))
				$account->setTeamName($teamName);
			
			if (!$account->addAccount($link))
				return false;
			
			return $account;
		}
		
		
		public static function getTeamsForTeamAdmin($userID, $pageOffset=0, $pageSize=10, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamsForTeamAdmin() could not get DB connection.");
					return false;
				}
			}
			
			$pageOffset = $pageSize * $pageOffset;
			
			
			$userID = mysql_real_escape_string($userID, $link);
			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT teamid FROM tdo_team_admins WHERE userid='$userID')";
			$sql .= " ORDER BY teamname LIMIT $pageSize OFFSET $pageOffset";
			
			if ($result = mysql_query($sql, $link))
			{
				$teams = array();
				while ($row = mysql_fetch_array($result))
				{
					if (isset($row['teamid']))
					{
						$team = TDOTeamAccount::teamFromRow($row);
						if ($team)
							$teams[] = $team;
					}
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $teams;
			}
			else
			{
				error_log("TDOTeamAccount::getTeamsForTeamAdmin() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function isAdminForTeam($userID, $teamID, $link=NULL)
		{
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::isAdminForTeam() called with empty userID");
				return false;
			}
			if (!isset($teamID) || $teamID == NULL)
			{
				// Look up the user's team ID
				$teamAccount = TDOTeamAccount::getTeamForTeamAdmin($userID, $link);
				if ($teamAccount)
				{
					$teamID = $teamAccount->getTeamID();
				}
				else
				{
					error_log("TDOTeamAccount::isAdminForTeam() called with empty teamID");
					return false;
				}
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isAdminForTeam() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT COUNT(*) FROM tdo_team_admins WHERE teamid='$teamID' AND userid='$userID'";
			$result = mysql_query($sql, $link);
			if($result)
			{
				$total = mysql_fetch_array($result);
				if ($total && isset($total[0]) && $total[0] > 0)
				{
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return true;
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function isBillingAdminForAnyTeam($userID, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isBillingAdminForAnyTeam() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$sql = "SELECT COUNT(*) FROM tdo_team_accounts WHERE billing_userid='$userID'";
			$result = mysql_query($sql, $link);
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']) && $row['0'] > 0)
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return true;
					}
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		// Return true if the userID belongs to ANY team
		public static function isTeamMember($userID, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isTeamMember() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$sql = "SELECT COUNT(*) FROM tdo_team_members WHERE userid='$userID'";
			$result = mysql_query($sql, $link);
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']) && $row['0'] > 0)
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return true;
					}
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getTeamIDForUser($userID, $membershipType=TEAM_MEMBERSHIP_TYPE_MEMBER, $link=NULL)
		{
			if (!isset($userID))
			{
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamIDForUser() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$tableName = "tdo_team_members";
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
				$tableName = "tdo_team_admins";
			$sql = "SELECT teamid FROM $tableName WHERE userid='$userID'";
			$result = mysql_query($sql, $link);
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						$teamID = $row['0'];
						
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $teamID;
					}
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		// This method returns one of the following statuses:
		//
		//	TEAM_SUBSCRIPTION_STATE_EXPIRED
		//	TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD
		//	TEAM_SUBSCRIPTION_STATE_ACTIVE
		//	TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD
		public static function getTeamSubscriptionStatus($teamID, $link=NULL)
		{
			$team = TDOTeamAccount::getTeamForTeamID($teamID, NULL, $link);
			if (!$team)
			{
				return false;
			}
			
			$expirationDate = $team->getExpirationDate();
			$teamCreationDate = $team->getCreationDate();
			
			$systemSettingTeamExpirationGracePeriodDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_EXPIRATION_GRACE_PERIOD_DATE_INTERVAL);
			$gracePeriodDateInterval = new DateInterval($systemSettingTeamExpirationGracePeriodDateIntervalSetting);
			$gracePeriodDate = new DateTime('@' . $expirationDate, new DateTimeZone("UTC"));
			$gracePeriodDate = $gracePeriodDate->add($gracePeriodDateInterval);
			$gracePeriodTimestamp = $gracePeriodDate->getTimestamp();
			
			$systemSettingTeamTrialPeriodDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL);
			$trialPeriodDateInterval = new DateInterval($systemSettingTeamTrialPeriodDateIntervalSetting);
			$trialPeriodDate = new DateTime('@' . $teamCreationDate, new DateTimeZone("UTC"));
			$trialPeriodDate = $trialPeriodDate->add($trialPeriodDateInterval);
			$trialPeriodTimestamp = $trialPeriodDate->getTimestamp();
			
			$now = time();
			if ($now < $trialPeriodTimestamp)
			{
				return TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD;
			}
			
			if ($now < $expirationDate)
			{
				return TEAM_SUBSCRIPTION_STATE_ACTIVE;
			}
			
			if ($now < $gracePeriodTimestamp)
			{
				return TEAM_SUBSCRIPTION_STATE_GRACE_PERIOD;
			}
			
			return TEAM_SUBSCRIPTION_STATE_EXPIRED;
		}
		
		
		public static function getTeamForTeamMember($userID, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamForTeamMember() could not get DB connection.");
					return false;
				}
			}
			
			
			$userID = mysql_real_escape_string($userID, $link);
			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT teamid FROM tdo_team_members WHERE userid='$userID')";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['teamid']))
				{
					$team = TDOTeamAccount::teamFromRow($row);
					if ($team)
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						
						return $team;
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamForTeamMember() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getTeamForTeamAdmin($adminUserID, $link=NULL)
		{
			if (!isset($adminUserID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamForTeamAdmin() could not get DB connection.");
					return false;
				}
			}
			
			
			$adminUserID = mysql_real_escape_string($adminUserID, $link);
			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT teamid FROM tdo_team_admins WHERE userid='$adminUserID')";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['teamid']))
				{
					$team = TDOTeamAccount::teamFromRow($row);
					if ($team)
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						
						return $team;
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getTeamForTeamAdmin() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getAllTeams($pageOffset=0, $pageSize=10, $link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAllTeamsForUser() could not get DB connection.");
					return false;
				}
			}
			
			$pageOffset = $pageSize * $pageOffset;
			
			$sql = "SELECT * FROM tdo_team_accounts ORDER BY creation_date LIMIT $pageSize OFFSET $pageOffset";
			
			if ($result = mysql_query($sql, $link))
			{
				$teams = array();
				while ($row = mysql_fetch_array($result))
				{
					if (isset($row['teamid']))
					{
						$team = TDOTeamAccount::teamFromRow($row);
						if ($team)
							$teams[] = $team;
					}
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $teams;
			}
			else
			{
				error_log("TDOTeamAccount::getAllTeams() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getAllTeamsForUser($userID, $pageOffset=0, $pageSize=10, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAllTeamsForUser() could not get DB connection.");
					return false;
				}
			}
			
			$pageOffset = $pageSize * $pageOffset;
			
			
			$userID = mysql_real_escape_string($userID, $link);
			// select * from tdo_team_accounts WHERE teamid IN (SELECT tdo_team_admins.teamid FROM tdo_team_admins LEFT JOIN tdo_team_members ON tdo_team_admins.userid=tdo_team_members.userid WHERE tdo_team_admins.userid='6111b8a6-5210-6ee9-baac-00006ab40e22');
			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT T1.teamid FROM tdo_team_admins AS T1 LEFT JOIN tdo_team_members AS T2 ON T1.teamid=T2.teamid WHERE (T1.userid='$userID' OR T2.userid='$userID'))";
			$sql .= " ORDER BY teamname LIMIT $pageSize OFFSET $pageOffset";
			
			if ($result = mysql_query($sql, $link))
			{
				$teams = array();
				while ($row = mysql_fetch_array($result))
				{
					if (isset($row['teamid']))
					{
						$team = TDOTeamAccount::teamFromRow($row);
						if ($team)
							$teams[] = $team;
					}
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $teams;
			}
			else
			{
				error_log("TDOTeamAccount::getAllTeamsForUser() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		public static function getAllTeamsCountForUser($userID, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAllTeamsCountForUser() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			// select * from tdo_team_accounts WHERE teamid IN (SELECT tdo_team_admins.teamid FROM tdo_team_admins LEFT JOIN tdo_team_members ON tdo_team_admins.userid=tdo_team_members.userid WHERE tdo_team_admins.userid='6111b8a6-5210-6ee9-baac-00006ab40e22');
			$sql = "SELECT COUNT(*) FROM tdo_team_accounts WHERE teamid IN (SELECT T1.teamid FROM tdo_team_admins AS T1 LEFT JOIN tdo_team_members AS T2 ON T1.teamid=T2.teamid WHERE (T1.userid='$userID' OR T2.userid='$userID'))";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getAllTeamsCountForUser() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getAdministeredTeamsCountForAdmin($userID, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAdministeredTeamsCountForAdmin() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			// select * from tdo_team_accounts WHERE teamid IN (SELECT tdo_team_admins.teamid FROM tdo_team_admins LEFT JOIN tdo_team_members ON tdo_team_admins.userid=tdo_team_members.userid WHERE tdo_team_admins.userid='6111b8a6-5210-6ee9-baac-00006ab40e22');
			$sql = "SELECT COUNT(*) FROM tdo_team_accounts WHERE teamid IN (SELECT teamid FROM tdo_team_admins WHERE userid='$userID')";
			
			$result = mysql_query($sql, $link);
			
			if($result)
			{
				if($row = mysql_fetch_array($result))
				{
					if(isset($row['0']))
					{
						if ($closeLink)
							TDOUtil::closeDBLink($link);
						return $row['0'];
					}
				}
			}
			else
			{
				error_log("TDOTeamAccount::getAdministeredTeamsCountForAdmin() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getAdministeredTeamsForAdmin($userID, $pageOffset=0, $pageSize=10, $link=NULL)
		{
			if (!isset($userID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAdministeredTeamsForAdmin() could not get DB connection.");
					return false;
				}
			}
			
			$pageOffset = $pageSize * $pageOffset;
			
			
			$userID = mysql_real_escape_string($userID, $link);
			// select * from tdo_team_accounts WHERE teamid IN (SELECT tdo_team_admins.teamid FROM tdo_team_admins LEFT JOIN tdo_team_members ON tdo_team_admins.userid=tdo_team_members.userid WHERE tdo_team_admins.userid='6111b8a6-5210-6ee9-baac-00006ab40e22');
			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT teamid FROM tdo_team_admins WHERE userid='$userID')";
//			$sql = "SELECT * FROM tdo_team_accounts WHERE teamid IN (SELECT T1.teamid FROM tdo_team_admins AS T1 LEFT JOIN tdo_team_members AS T2 ON T1.teamid=T2.teamid WHERE (T1.userid='$userID' OR T2.userid='$userID'))";
			$sql .= " ORDER BY teamname LIMIT $pageSize OFFSET $pageOffset";
			
			if ($result = mysql_query($sql, $link))
			{
				$teams = array();
				while ($row = mysql_fetch_array($result))
				{
					if (isset($row['teamid']))
					{
						$team = TDOTeamAccount::teamFromRow($row);
						if ($team)
							$teams[] = $team;
					}
				}
				
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $teams;
			}
			else
			{
				error_log("TDOTeamAccount::getAdministeredTeamsForAdmin() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		public static function getMainListIDForTeam($teamID, $link=NULL)
		{
			if (!isset($teamID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getMainListIDForTeam() could not get DB connection.");
					return false;
				}
			}
			
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT main_listid FROM tdo_team_accounts WHERE teamid='$teamID'";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['main_listid']))
				{
					$listid = $row['main_listid'];
					
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					
					return $listid;
				}
			}
			else
			{
				error_log("TDOTeamAccount::getMainListIDForTeam() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		//
		// Returns true if the specified team was created before the launch of
		// Todo for Business v1.0. This allows us to grandfather in the pricing
		// of old team accounts.
		public static function isGrandfatheredTeam($teamID, $link=NULL)
		{
			if (!isset($teamID))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isGrandfatheredTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT creation_date FROM tdo_team_accounts WHERE teamid='$teamID'";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['creation_date']))
				{
					$creationDate = $row['creation_date'];
					
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					
					// If the creationDate is before the grandfather date, this
					// is a grandfathered team.
					$systemSettingTeamGrandfatherDateString = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_DATE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_DATE);
					$grandfatherDate = strtotime($systemSettingTeamGrandfatherDateString);
					
					if ($creationDate < $grandfatherDate)
						return true;
				}
			}
			else
			{
				error_log("TDOTeamAccount::isGrandfatheredTeam() failed during query ($sql): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		
		//
		// Returns an array with the following elements:
		//
		//	billingFrequency: monthly | yearly
		//	unitPrice: the price of 1 monthly or yearly item
		//	unitCombinedPrice: unitPrice x number of subscriptions
		//	numOfSubscriptions: the number of subscriptions being purchased
		//	discountPercentage: 0, 5, 10, or 20 (for 0%, 5%, 10%, or 20%)
		//	discountAmount: a dollar amount for the discount (not a negative number, so use accordingly)
		//
		//  teamCredits:
		//  teamCreditMonths
		//  creditsPriceDiscount
		//
		//	subtotal: unitCombinedPrice less any discount
		//	totalPrice
		//
		public static function getTeamPricingInfo($billingFrequency, $numberOfSubscriptions, $isAutoRenewal=false, $teamAdminUserID=NULL, $teamID=NULL, $link=NULL)
		{
			if (!isset($billingFrequency))
			{
				error_log("TDOTeamAccount::getTeamPricingInfo() called with empty billingFrequency parameter.");
				return false;
			}
			
			if (($billingFrequency != "monthly" && $billingFrequency != "yearly") && ($billingFrequency != "1" && $billingFrequency != "2"))
			{
				error_log("TDOTeamAccount::getTeamPricingInfo() called with an invalid billing frequency. Expecting 'monthly' or 'yearly' and received: " . $billingFrequency);
				return false;
			}
			
			$numOfSubscriptions = intval($numberOfSubscriptions);
			
			if ($numOfSubscriptions <= 0 || $numOfSubscriptions > 500)
			{
				error_log("TDOTeamAccount::getTeamPricingInfo() called with an invalid numberOfSubscriptions value. Must be 1 or greater and 500 or less.");
				return false;
			}
			
			// If a teamID is specified, we need to look up the team and see how
			// many total subscriptions this will end up being so we can use
			// this number to calculate the discount. For example, if a team
			// currently has 4 members and they are purchasing one additional,
			// this would be 5 total members, which qualifies them for a 5%
			// discount on the new subscription they are purchasing right now.
			$numOfNewTotalSubscriptions = $numOfSubscriptions;
			if (!$isAutoRenewal && (!empty($teamID)))
			{
				$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
				if ($teamAccount)
				{
					$currentMemberCount = $teamAccount->getLicenseCount();
					if ($currentMemberCount > 0)
						$numOfNewTotalSubscriptions = $currentMemberCount + $numOfSubscriptions;
				}
			}
			
			if ($numOfNewTotalSubscriptions <= 0 || $numOfNewTotalSubscriptions > 500)
			{
				error_log("TDOTeamAccount::getTeamPricingInfo() called with numberOfSubscriptions value that would make the team ($teamID) exceed 500 members.");
				return false;
			}
			
			$result = array();
			$result['billingFrequency'] = $billingFrequency;
			
			$subscriptionType = SUBSCRIPTION_TYPE_MONTH;
			if ($billingFrequency == "yearly" || $billingFrequency == "2")
				$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
			
			$isGrandfatheredTeam = false;
			if (!empty($teamID))
			{
				$isGrandfatheredTeam = TDOTeamAccount::isGrandfatheredTeam($teamID, $link);
			}
			
			$unitPrice = TDOTeamAccount::unitCostForBillingFrequency($subscriptionType, $isGrandfatheredTeam);
			$result['unitPrice'] = $unitPrice;
			
			$unitCombinedPrice = $unitPrice * $numOfSubscriptions;
			$result['unitCombinedPrice'] = $unitCombinedPrice;
			$result['numOfSubscriptions'] = $numOfSubscriptions;
			
			// Use the total number of proposed team members on an existing
			// team to calculate the discount percentage.
			$discountPercentage = TDOTeamAccount::discountPercentageForNumberOfMembers($numOfNewTotalSubscriptions);
			$result['discountPercentage'] = $discountPercentage;
			
			$discountFactor = $discountPercentage / 100.0;
			$discountAmount = sprintf("%.2f", round($unitCombinedPrice * $discountFactor, 2));
			$result['discountAmount'] = $discountAmount;
			
			$subtotalBeforeCredits = $unitCombinedPrice - $discountAmount;
			if ($subtotalBeforeCredits < 0) // Safety check
				$subtotalBeforeCredits = 0;
			
			$teamCreditsInfo = TDOTeamAccount::teamCreditsInfoForStartingPrice($teamID, $subtotalBeforeCredits, $link);
			if (!empty($teamCreditsInfo))
			{
				$result['teamCredits'] = $teamCreditsInfo['teamCredits'];
				$result['teamCreditMonths'] = $teamCreditsInfo['teamCreditMonths'];
				$result['creditsPriceDiscount'] = $teamCreditsInfo['creditsPriceDiscount'];
				$subtotal = sprintf("%.2f", round($subtotalBeforeCredits - $teamCreditsInfo['creditsPriceDiscount'], 2));
			}
			
//			$subtotal = sprintf("%.2f", round($unitCombinedPrice - $discountAmount, 2));
			$result['subtotal'] = $subtotal;
						
			$totalPrice = sprintf("%.2f", round($subtotal, 2));
			$result['totalPrice'] = $totalPrice;
			
			return $result;
		}
		
		
		public static function teamCreditsInfoForStartingPrice($teamID, $proposedCharge, $link=NULL)
		{
			if ($proposedCharge <= 0)
				return NULL;
			
			$result = array();
			
			// As of Todo Cloud Web 2.4 (Todo for Business), we now allow team
			// members to donate their remaining personal subscription months to
			// a team. When they do this, we give the discount during the next
			// auto-renewal. So, calculate this discount here.
			$numOfMonthCreditsHarvested = 0;
			$priceOfMonthCreditsHarvested = 0;
			$teamCredits = TDOTeamAccount::activeCreditsForTeam($teamID, $link);
			if ($teamCredits)
			{
				$isGrandfatheredTeam = false;
				if (!empty($teamID))
				{
					$isGrandfatheredTeam = TDOTeamAccount::isGrandfatheredTeam($teamID, $link);
				}
				
				$teamMonthlyPerUserPrice = TDOTeamAccount::unitCostForBillingFrequency(SUBSCRIPTION_TYPE_MONTH, $isGrandfatheredTeam);
				
				// We have to know the number of months that are needed to pay
				// for this renewal so we know how many credits to consume.
				$numOfMonthCreditsNeeded = ceil($proposedCharge/$teamMonthlyPerUserPrice);
				
				// teamCredits are in an array with the following info:
				//		userID:
				//		numOfMonths
				//
				//		While we enumerate the list, we will record how many
				//		credits per each user are consumed so that we can
				//		properly track credits for future use.
				//
				//		numOfMonthsHarvested
				
				foreach($teamCredits as $key => $credit)
				{
					if ($numOfMonthCreditsHarvested >= $numOfMonthCreditsNeeded)
						break;
					
					if (!isset($credit['numOfMonths']))
						continue;
					
					$slotsLeft = $numOfMonthCreditsNeeded - $numOfMonthCreditsHarvested;
					
					$numOfMonthCreditsAvailable = $credit['numOfMonths'];
					
					$numOfMonthCreditsRemaining = 0;
					$numOfMonthCreditsToHarvest = 0;
					if ($slotsLeft >= $numOfMonthCreditsAvailable)
					{
						$numOfMonthCreditsToHarvest = $numOfMonthCreditsAvailable;
					}
					else
					{
						$numOfMonthCreditsToHarvest = $slotsLeft;
						$numOfMonthCreditsRemaining = $numOfMonthCreditsAvailable - $numOfMonthCreditsToHarvest;
					}
					
					$numOfMonthCreditsHarvested += $numOfMonthCreditsToHarvest;
					$teamCredits[$key]['numOfMonthsHarvested'] = $numOfMonthCreditsToHarvest;
					$teamCredits[$key]['numOfMonthsRemaining'] = $numOfMonthCreditsRemaining;
				}
				
				$priceOfMonthCreditsHarvested = $numOfMonthCreditsHarvested * $teamMonthlyPerUserPrice;
				
				$result['teamCredits'] = $teamCredits;
			}
			
			if ($priceOfMonthCreditsHarvested > $proposedCharge)
				$priceOfMonthCreditsHarvested = $proposedCharge;
			
			$result['teamCreditMonths'] = $numOfMonthCreditsHarvested;
			$result['creditsPriceDiscount'] = $priceOfMonthCreditsHarvested;
			
			return $result;
		}
		
		
		// This function reads through the teamCredits array and updates the
		// database to update the consumed team credits. The teamCredits array
		// has the following entries on each item:
		//		userid					- the user who donated it
		//		donationDate			- the donation date
		//		numOfMonths				- the value before the purchase
		//		numOfMonthsHarvested	- number of months that were used
		//		numOfMonthsRemaining	- number of months that needs to be set
		//
		// CRITICAL: It is important to pass the $teamCredits in the same order
		// that it was originally returned by the system. Once this function
		// comes across a team credit that has numOfMonthsHarvested to be empty
		// or 0, processing will stop.
		public static function consumeTeamCreditsAfterPurchase($teamID, $teamCredits)
		{
			foreach($teamCredits as $credit)
			{
				if (empty($credit['numOfMonthsHarvested']) || $credit['numOfMonthsHarvested'] == 0)
				{
					// No more team credits exist that were just harvested, so
					// we are finished processing. :)
					break;
				}
				
				$userID = $credit['userid'];
				$donationDate = $credit['donationDate'];
				
				if ($credit['numOfMonthsRemaining'] == 0)
				{
					// This team credit was fully consumed and so we can just
					// update the single team credit to show the date it was
					// consumed.
					if (!TDOTeamAccount::markTeamCreditAsConsumed($teamID, $userID, $donationDate))
					{
						error_log('TDOTeamAccount::consumeTeamCreditsAfterPurchase() had an error marking a team ($teamID) credit as consumed for user ($userID)');
						continue;
					}
				}
				else
				{
					// In this condition, we didn't consume all of the available
					// months donated by a user, so add a new record that's
					// marked consumed and then adjust the original.
					
					$numOfMonthsHarvested = $credit['numOfMonthsHarvested'];
					$numOfMonthsRemaining = $credit['numOfMonthsRemaining'];
					
					if (!TDOTeamAccount::saveSubscriptionMonthsToTeamCredit($teamID, $userID, $numOfMonthsHarvested, true))
					{
						error_log('TDOTeamAccount::consumeTeamCreditsAfterPurchase() had an error recording $numOfMonthsHarvested month(s) as consumed by a team ($teamID) donated by user ($userID)');
						continue;
					}
					
					if (!TDOTeamAccount::updateTeamCreditWithNumberOfMonths($teamID, $userID, $donationDate, $numOfMonthsRemaining))
					{
						error_log('TDOTeamAccount::consumeTeamCreditsAfterPurchase() had an error recording $numOfMonthsHarvested month(s) as consumed by a team ($teamID) donated by user ($userID)');
						continue;
					}
				}
			}
			
			return true;
		}
		
		
		public static function updateTeamCreditWithNumberOfMonths($teamID, $userID, $donationDate, $numOfMonths, $link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamCreditWithNumberOfMonths() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$userID = mysql_real_escape_string($userID, $link);
			
			$sql = "UPDATE tdo_team_subscription_credits SET donation_months_count=$numOfMonths WHERE teamid='$teamID' AND userid='$userID' AND donation_date=$donationDate";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateTeamCreditWithNumberOfMonths() unable to update the team credit for team ($teamID) and user ($userID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		public static function markTeamCreditAsConsumed($teamID, $userID, $donationDate, $link=NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::markTeamCreditAsConsumed() could not get DB connection.");
					return false;
				}
			}
			
			$consumedDate = time();
			$teamID = mysql_real_escape_string($teamID, $link);
			$userID = mysql_real_escape_string($userID, $link);
			
			$sql = "UPDATE tdo_team_subscription_credits SET consumed_date=$consumedDate WHERE teamid='$teamID' AND userid='$userID' AND donation_date=$donationDate";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::markTeamCreditAsConsumed() unable to mark a team credit as consumed for team ($teamID) and user ($userID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		// Returns:
		//	0 - no discount
		//	5 - 5% discount
		//	10 - 10% discount
		//	20 - 20% discount
		public static function discountPercentageForNumberOfMembers($numberOfSubscriptions)
		{
			return 0;// always return 0 discount by members
//			if (empty($numberOfSubscriptions) || $numberOfSubscriptions <= 0)
//				return 0;
//			
//			$discountPercentage = 0;
//			if ($numberOfSubscriptions >= 20)
//				$discountPercentage = 20;
//			else if ($numberOfSubscriptions >= 10)
//				$discountPercentage = 10;
//			else if ($numberOfSubscriptions >= 5)
//				$discountPercentage = 5;
//			
//			return $discountPercentage;
		}
		
		
		public static function unitCostForBillingFrequency($billingFrequency, $isGrandfatheredTeam=false)
		{
			if ($isGrandfatheredTeam)
			{
				if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
					return TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_YEARLY_PRICE);
				else
					return TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE', DEFAULT_SYSTEM_SETTING_TEAM_GRANDFATHER_MONTHLY_PRICE);
			}
			else
			{
				if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
					return TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_YEARLY_PRICE_PER_USER);
				else
					return TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER', DEFAULT_SYSTEM_SETTING_TEAM_MONTHLY_PRICE_PER_USER);
			}
		}
		
		
		// Returns an associative array with the following elements:
		//
		//	newExpirationDate: string in the format of 'd M Y'
		//	newNumOfMembers: # of total members
		//	billingFrequency: 1 = monthly, 2 = yearly
		//	bulkDiscount: float value of the discount amount (if any)
		//	discountPercentage: int value of discount percentage if any or 0
		//	currentAccountCredit: float value of credit from current account being deducted from charge
		//	totalCharge: float value of total charge needed to make the change
		//
		// This method basically mimics the updateChangeDisplayInfo() function
		// from TeamFunctions.js, but since it comes from the server, it's
		// authoritative.
		public static function getTeamChangePricingInfo($billingFrequency, $numberOfSubscriptions, $teamID)
		{
			if (!isset($billingFrequency))
			{
				error_log("TDOTeamAccount::getTeamChangePricingInfo() called with empty billingFrequency parameter.");
				return false;
			}
			
			if (($billingFrequency != SUBSCRIPTION_TYPE_MONTH) && ($billingFrequency != SUBSCRIPTION_TYPE_YEAR))
				$billingFrequency = SUBSCRIPTION_TYPE_MONTH; // default to monthly
			
			if (!isset($numberOfSubscriptions))
			{
				error_log("TDOTeamAccount::getTeamChangePricingInfo() called with empty numberOfSubscriptions parameter.");
				return false;
			}
			
			if ($numberOfSubscriptions <= 0)
				$numberOfSubscriptions = 1; // don't let there ever be less than 1
			
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getTeamChangePricingInfo() called with empty teamID parameter.");
				return false;
			}
			
			$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
			if (!$teamAccount)
			{
				error_log("TDOTeamAccount::getTeamChangePricingInfo() could not get read the team for the teamID ($teamID).");
				return false;
			}
			
			$origLicenseCount = $teamAccount->getLicenseCount();
			$origNewLicenseCount = $teamAccount->getNewLicenseCount();
			$origBillingFrequency = $teamAccount->getBillingFrequency();
			$origExpirationDate = $teamAccount->getExpirationDate();
			
			$isGrandfatheredTeam = false;
			if (!empty($teamID))
			{
				$isGrandfatheredTeam = TDOTeamAccount::isGrandfatheredTeam($teamID);
			}
			
			$unitCost = TDOTeamAccount::unitCostForBillingFrequency($billingFrequency, $isGrandfatheredTeam);
			$discountPercentage = TDOTeamAccount::discountPercentageForNumberOfMembers($numberOfSubscriptions);
			$discountRate = $discountPercentage / 100;
			
			$now = time();
			$oneDayInSeconds = 60 * 60 * 24;
			$oneYearInSeconds = $oneDayInSeconds * 365;
			$oneMonthInSeconds = $oneDayInSeconds * 31;
			$secondsLeft = 0;
			if ($now < $origExpirationDate)
				$secondsLeft = $origExpirationDate - $now;
			$monthsLeft = 0;
			$monthsLeft = round($secondsLeft / $oneMonthInSeconds);
//error_log("secondsLeft: $secondsLeft, oneMonthInSeconds: $oneMonthInSeconds, origExpirationDate: $origExpirationDate, now: $now, monthsLeft: $monthsLeft");
			
			$currentAccountCredit = 0.0;
			$bulkDiscount = 0.0;
			$totalCharge = 0.0;
			// Set the new expiration date to match the existing expiration date
			$newExpirationDate = $origExpirationDate;
			
			$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
			$oneMonthDateInterval = new DateInterval($oneMonthPeriodSetting);
			
			$oneYearPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
			$oneYearDateInterval = new DateInterval($oneYearPeriodSetting);
			
			$monthlyExpirationDateString = "";
			$yearlyExpirationDateString = "";
			
			// If the current subscription is already expired, we need to adjust
			// some values to get the pricing code to process properly.
			if ($origExpirationDate < $now)
			{
				// Set the original license counts to 0 so they will be fully
				// charged.
				$origLicenseCount = 0;
				$origNewLicenseCount = 0;
				$nowDate = new DateTime("now", new DateTimeZone("UTC"));
				$oneMonthFromNowDate = $nowDate->add($oneMonthDateInterval);
				$nowDate = new DateTime("now", new DateTimeZone("UTC"));
				$oneYearFromNowDate = $nowDate->add($oneYearDateInterval);
				if ($billingFrequency == SUBSCRIPTION_TYPE_MONTH)
				{
					$newExpirationDate = $oneMonthFromNowDate->getTimestamp();
					$monthsLeft = 1;
				}
				else
				{
					$newExpirationDate = $oneYearFromNowDate->getTimestamp();
					$monthsLeft = 12;
				}
				
				// Return the monthly and yearly possible expiration dates so
				// that the Javascript can display the right expiration dates
				// to the end user.
				$monthlyExpirationDateString = $oneMonthFromNowDate->format("j F Y");
				$yearlyExpirationDateString = $oneYearFromNowDate->format("j F Y");

                $monthlyExpirationDate = $oneMonthFromNowDate;

                $yearlyExpirationDate = $oneYearFromNowDate;
			}
			else
			{
				// Return the monthly and yearly possible expiration dates so
				// that the Javascript can display the right expiration dates
				// to the end user.
				$monthlyStartDate = new DateTime('@' . $origExpirationDate, new DateTimeZone("UTC"));
				$monthlyExpirationDate = $monthlyStartDate->add($oneMonthDateInterval);
				
				$yearlyStartDate = new DateTime('@' . $origExpirationDate, new DateTimeZone("UTC"));
				$yearlyExpirationDate = $yearlyStartDate->add($oneYearDateInterval);
			}
			
			
			// Scenario 0: Nothing has changed
			if ((($billingFrequency == $origBillingFrequency) && ($numberOfSubscriptions == $origNewLicenseCount)) || TDOTeamAccount::getTeamSubscriptionStatus($teamID) === TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD)
			{
				// Nothing to do. Intentionally blank.
			}
			else if ($billingFrequency == $origBillingFrequency)
			{
				if ($numberOfSubscriptions < $origLicenseCount)
				{
					//
					// Scenario 1: reduce the number of team members only.
					//
					// Nothing to do.
					//
				}
				else if ($numberOfSubscriptions > $origLicenseCount)
				{
					// We need to charge for the new users, but only if there
					// is 1 or more month left in the subscription.
					if ($monthsLeft > 0)
					{
						$numOfNewPaidMembers = $numberOfSubscriptions - $origLicenseCount;
						
						if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
						{
							$monthlyCost = $unitCost / 12;
							$baseCost = $monthlyCost * $monthsLeft;
							$subtotal = $numOfNewPaidMembers * $baseCost;
						}
						else
						{
							$subtotal = $numOfNewPaidMembers * $unitCost;
						}
						
						$bulkDiscount = $subtotal * $discountRate;
						$totalCharge = $subtotal - $bulkDiscount;
					}
				}
			}
			else if (($origBillingFrequency == SUBSCRIPTION_TYPE_MONTH) && ($billingFrequency == SUBSCRIPTION_TYPE_YEAR))
			{
				// Changing billing from MONTHLY to YEARLY
				
				// New expiration date is ALWAYS ONE year from NOW
				$nowDate = new DateTime("now", new DateTimeZone("UTC"));
				$oneYearFromNowDate = $nowDate->add($oneYearDateInterval);
				$newExpirationDate = $oneYearFromNowDate->getTimestamp();
				
				$baseCost = $numberOfSubscriptions * $unitCost;
				$bulkDiscount = $baseCost * $discountRate;
				$subtotal = $baseCost - $bulkDiscount;
				
//				error_log("e1: baseCost: $baseCost, bulkDiscount: $bulkDiscount, subtotal: $subtotal, unitCost: $unitCost, monthsLeft: $monthsLeft");
				
				// If there is time left on the current account, deduct a credit
				if ($monthsLeft > 0)
				{
					$origDiscountPercentage = TDOTeamAccount::discountPercentageForNumberOfMembers($origLicenseCount);
					$origDiscountRate = $origDiscountPercentage / 100;
					$origUnitCost = TDOTeamAccount::unitCostForBillingFrequency($origBillingFrequency, $isGrandfatheredTeam);
					$origCost = $origLicenseCount * $origUnitCost;
					$origBulkDiscount = $origCost * $origDiscountRate;
					$origCost = $origCost - $origBulkDiscount;
					
					$currentAccountCredit = $origCost * $monthsLeft;
					
//					error_log("f: currentAccountCredit: $currentAccountCredit, origCost: $origCost, monthsLeft: $monthsLeft");
				}
				
//				error_log("g: subtotal: $subtotal");
				$totalCharge = $subtotal - $currentAccountCredit;
//				error_log("g: totalCharge: $totalCharge");
			}
			else if (($origBillingFrequency == SUBSCRIPTION_TYPE_YEAR) && ($billingFrequency == SUBSCRIPTION_TYPE_MONTH))
			{
				if (($origExpirationDate < $now) || ($monthsLeft == 0))
				{
					// This account has already expired. Need to charge anew for
					// whatever the user has selected.
					$nowDate = new DateTime("now", new DateTimeZone("UTC"));
					$oneMonthFromNowDate = $nowDate->add($oneMonthDateInterval);
					$newExpirationDate = $oneMonthFromNowDate->getTimestamp();
					$baseCost = $numberOfSubscriptions * $unitCost;
					$bulkDiscount = $baseCost * $discountRate;
					$totalCharge = $baseCost - $bulkDiscount;
				}
				else
				{
					if ($numberOfSubscriptions <= $origLicenseCount)
					{
						// Nothing changes other than the billing frequency.
						// When the next billing date occurs, they will be
						// billed monthly.
						
						// No need to charge anything.
					}
					else
					{
						// The expiration date needs to be adjusted to account
						// for more people being added into the team
						// subscription.
						$origUnitCost = TDOTeamAccount::unitCostForBillingFrequency($origBillingFrequency, $isGrandfatheredTeam);
						$origPayment = $origUnitCost * $origLicenseCount;
						$origPaymentPerMonth = $origPayment / 12;
						$origCredit = $origPaymentPerMonth * $monthsLeft;
						$newMonthlyCost = $numberOfSubscriptions * $unitCost;
						
						if ($origCredit > $newMonthlyCost)
						{
							// Determine how many months are left in the
							// subscription and adjust the new expiration date
							// accordingly.
							$monthsAvailable = round(($origCredit / $newMonthlyCost), 2);
							
							$adjustedDate = new DateTime("now", new DateTimeZone("UTC"));
							for ($i = 0; $i < $monthsAvailable; $i++)
							{
								$adjustedDate->add($oneMonthDateInterval);
							}
							$newExpirationDate = $adjustedDate->getTimestamp();
							
							// No need to charge anything
						}
						else
						{
							// In this case, the original credit does NOT cover
							// the new cost of the total number of members being
							// added and the user will need to pay to make up
							// the difference.
							
							// Set the new expiration date to one month from now.
							$nowDate = new DateTime("now", new DateTimeZone("UTC"));
							$oneMonthFromNowDate = $nowDate->add($oneMonthDateInterval);
							$newExpirationDate = $oneMonthFromNowDate->getTimestamp();
							
							$unpaidAmount = $newMonthlyCost - $origCredit;
							$bulkDiscount = $unpaidAmount * $discountRate;
							$totalCharge = $unpaidAmount - $bulkDiscount;
						}
					}
				}
			}
			
			if ($totalCharge <= 0)
			{
				$bulkDiscount = 0;
				$currentAccountCredit = 0;
				$totalCharge = 0;
			}
			
			// Now go through every single one of the values and make sure they
			// are 2 decimal places (we'll send them back as text values).
			
//			error_log("p: Bulk Discount: $bulkDiscount");
			$result = array(
							"newExpirationDate" => $newExpirationDate,
							"newExpirationDateString" => date('d M Y', $newExpirationDate),
							"newNumOfMembers" => $numberOfSubscriptions,
							"billingFrequency" => $billingFrequency,
							"bulkDiscount" => sprintf("%.2f", round($bulkDiscount, 2)),
							"discountPercentage" => $discountPercentage,
							"currentAccountCredit" => sprintf("%.2f", round($currentAccountCredit, 2)),
							"monthlyExpirationDate" => $monthlyExpirationDate->getTimestamp(),
							"monthlyExpirationDateString" => $monthlyExpirationDate->format("j F Y"),
							"yearlyExpirationDate" => $yearlyExpirationDate->getTimestamp(),
							"yearlyExpirationDateString" => $yearlyExpirationDate->format("j F Y")
							);
			
			// As of Todo Cloud Web 2.4 (Todo for Business), we now allow team
			// memebrs to donate their remaining personal subscription months to
			// a team. When they do this, we give the discount during the next
			// auto-renewal. So, calculate this dicount here.
			
			$teamCreditsInfo = TDOTeamAccount::teamCreditsInfoForStartingPrice($teamID, $totalCharge);
			if (!empty($teamCreditsInfo))
			{
				$result['teamCredits'] = $teamCreditsInfo['teamCredits'];
				$result['teamCreditMonths'] = $teamCreditsInfo['teamCreditMonths'];
				$result['creditsPriceDiscount'] = $teamCreditsInfo['creditsPriceDiscount'];
				$totalCharge = $totalCharge - $teamCreditsInfo['creditsPriceDiscount'];
			}
			
			// The javascript is keyed on the existence of the totalCharge, so
			// make sure it's false if there's no charge.
			if ($totalCharge > 0)
				$result['totalCharge'] = sprintf("%.2f", round($totalCharge, 2));
			else
				$result['totalCharge'] = false;
			
			
			
//			error_log("q: Total Charge: $totalCharge");
			
			return $result;
		}
		
		
		// Returns an associative array with teamid set on success or error set
		// for a failure.
		public static function changeTeamAccount($adminUserID, $teamID, $numOfMembers, $billingFrequency, $stripeToken, $link=NULL)
		{
			// Create the new team
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::changeTeamAccount() missing parameter adminUserID");
				return array("success" => false, "error" => "Missing parameter: adminUserID");
			}
			
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::changeTeamAccount() missing parameter teamID");
				return array("success" => false, "error" => "Missing parameter: teamID");
			}
			
			if (!isset($numOfMembers))
			{
				error_log("TDOTeamAccount::changeTeamAccount() missing parameter numOfMembers");
				return array("success" => false, "error" => "Missing parameter: numOfMembers");
			}
			
			if (!isset($billingFrequency))
			{
				error_log("TDOTeamAccount::changeTeamAccount() missing parameter billingFrequency");
				return array("success" => false, "error" => "Missing parameter: billingFrequency");
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::changeTeamAccount() could not get DB connection.");
					return array("success" => false, "error" => "Error connecting to the database.");
				}
			}
			
			
			// Make sure that the adminUserID is authorized to make changes
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::changeTeamAccount() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "User not authorized.");
			}
			
			// Make sure this is a team account that actually exists
			$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
			if (!$teamAccount)
			{
				error_log("TDOTeamAccount::changeTeamAccount() could not read the team for the teamID ($teamID).");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return array("success" => false, "error" => "Could not find the specified team.");
			}
			
			// Determine if there is a charge by getting the change pricing info
			
			$origLicenseCount = $teamAccount->getLicenseCount();
			
			$changeInfo = TDOTeamAccount::getTeamChangePricingInfo($billingFrequency, $numOfMembers, $teamID);
			if (!$changeInfo)
			{
				error_log("TDOTeamAccount::changeTeamAccount() could not determine change details after calling getTeamChangePricingInfo(), adminUserID: $adminUserID, teamID: $teamID.");
				return array("success" => false, "error" => "Could not determine pricing information.");
			}
			
			$totalCharge = $changeInfo['totalCharge'];
			$newExpirationDate = $changeInfo['newExpirationDate'];
			$newExpirationDateString = $changeInfo['newExpirationDateString'];
			$bulkDiscount = $changeInfo['bulkDiscount'];
			$discountPercentage = $changeInfo['discountPercentage'];
			$currentAccountCredit = $changeInfo['currentAccountCredit'];
			
			// Bypass the charges if the team is new and in the trial period
			$teamSubscriptionStatus = TDOTeamAccount::getTeamSubscriptionStatus($teamID);
            $link = TDOUtil::getDBLink();

			if (($teamSubscriptionStatus != TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD) && $totalCharge && ($totalCharge > 0))
			{
				// We need to make an actual charge with Stripe.
				$billingFrequencyString = _('mo');
				if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
					$billingFrequencyString = _('yr');
				
                $chargeDescription = sprintf(_('Todo Cloud Team Account Change - %s member(s), 1 %s'), $numOfMembers, $billingFrequencyString);
				
				$totalChargeInCents = $totalCharge * 100;
				$subtotal = $totalCharge + $bulkDiscount + $currentAccountCredit;
				$subtotalInCents = $subtotal * 100;
				$bulkDiscountInCents = $bulkDiscount * 100;
				$currentAccountCreditInCents = $currentAccountCredit * 100;
				
				$teamCreditMonths = 0;
				$teamCreditsPriceDiscountInCents = 0;
				
				if (!empty($teamID) && !empty($changeInfo['teamCredits']))
				{
					// Account for any outstanding team credits
					$teamCreditMonths = $changeInfo['teamCreditMonths'];
					$teamCreditsPriceDiscountInCents = $changeInfo['creditsPriceDiscount'];
				}
				
				// Only charge if the total is greater than $0
				if ($totalChargeInCents > 0)
				{
					// If the stripeToken isn't specified, try to get the last4 of
					// the billing user's credit card to make the charge.
					$last4 = NULL;
					if (empty($stripeToken))
					{
						// Get the last4 credit card numbers from Stripe based on
						// the billing user's Stripe information.
						$billingUserID = $teamAccount->getBillingUserID();
						if (empty($billingUserID))
						{
							error_log("TDOTeamAccount::changeTeamAccount() could not completed the team ($teamID) charge because no credit card information was given and there is no team billing user.");
							return array("success" => false, "error" => "You must enter credit card information or specify a billing administrator.");
						}
						
						// Get the last4 credit card numbers from Stripe.
						$billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($billingUserID);
						if (!$billingInfo)
						{
							error_log("TDOTeamAccount::changeTeamAccount() unable to get billing information for team ID: $teamID");
							return array("success" => false, "error" => "Billing information is not available from the billing administrator. Please enter new credit card information.");
						}
						
						$last4 = $billingInfo['last4'];
					}
					// TDOSubscription::getStripeCustomerID.
					
					
					// Process the payment
					$stripeCharge = TDOSubscription::makeStripeChargeForTeamChange($adminUserID,
																				   $teamID,
																				   $stripeToken,
																				   $last4,
																				   $chargeDescription,
																				   $billingFrequency,
																				   $newExpirationDateString,
																				   $numOfMembers,
																				   $subtotalInCents,
																				   $discountPercentage,
																				   $bulkDiscountInCents,
																				   $teamCreditMonths,
																				   $teamCreditsPriceDiscountInCents,
																				   $currentAccountCreditInCents,
																				   $totalChargeInCents);
					
					if (empty($stripeCharge) || isset($stripeCharge['errcode']))
					{
						error_log("TDOTeamAccount::changeTeamAccount() could not complete the team ($teamID) charge with Stripe. Error code = " . $stripeCharge['errcode'] . ", Error description = " . $stripeCharge['errdesc']);
						return array("success" => false, "error" => $stripeCharge['stripe_error_message']);
					}
					
					// Keep a record of the charge
					$nowTimestamp = time();
					$stripeCustomerID = NULL;
					if (isset($stripeCharge['customer']))
						$stripeCustomerID = $stripeCharge['customer'];
					else
						$stripeCustomerID = TDOSubscription::getStripeCustomerID($adminUserID);
					
					if (empty($stripeCustomerID))
					{
						error_log("TDOTeamAccount::changeTeamAccount() failed to retrieve admin ($adminUserID) stripeCustomerID for for team ($teamID)");
						
						// Email the support team to let them know a charge was successful
						// but for whatever reason, we weren't able to find a Stripe
						// Customer ID, which means we could not log the purchase.
						TDOMailer::sendSubscriptionLogErrorNotification($teamID);
					}
					else
					{
						$cardType = 'N/A';
						$last4 = 'XXXX';
						if (isset($stripeCharge->card))
						{
							$card = $stripeCharge->card;
							if (isset($card['type']))
								$cardType = $card['type'];
							if (isset($card['last4']))
								$last4 = $card['last4'];
						}
						
						TDOSubscription::logStripePayment($adminUserID, $teamID, $numOfMembers, $stripeCustomerID, $stripeCharge->id, $cardType, $last4, $billingFrequency, $stripeCharge->amount, $nowTimestamp, $chargeDescription);
						TDOSubscription::addOrUpdateUserPaymentSystemInfo($adminUserID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);
					}
				}
				else
				{
					// We need to email the billing user and also log some sort of
					// payment so it shows up in the team's purchase history.
					$username = TDOUser::usernameForUserId($adminUserID);
					$displayName = TDOUser::displayNameForUserId($adminUserID);
					$purchaseDate = time();
					$cardType = "-";
					$last4 = "xxxx";

					$unitPrice = $pricingInfo['unitPrice'];
					$unitCombinedPrice = $pricingInfo['unitCombinedPrice'];
					$discountAmount = $pricingInfo['discountAmount'];
					$teamCreditsDiscountAmount = $pricingInfo['creditsPriceDiscount'];
					$subtotalAmount = $pricingInfo['subtotal'];
					$purchaseAmount = $pricingInfo['totalPrice'];
					
					// Send a completely full purchase receipt for a team account
					TDOMailer::sendTeamChangePurchaseReceipt($username, $displayName, $teamID, $purchaseDate,
															 $cardType, $last4, $billingFrequency,
															 $newExpirationDateString, $numOfMembers,
															 $subtotal, $bulkDiscount, $discountPercentage,
															 $teamCreditMonths, $teamCreditsPriceDiscountInCents,
															 $currentAccountCredit, $totalCharge);
					
					$stripeUserID = "-";
					$stripeChargeID = "-";
					
					TDOSubscription::logStripePayment($adminUserID, $teamID, $numOfMembers,
													  "-", "-", $cardType, $last4,
													  $billingFrequency, $totalChargeInCents,
													  $purchaseDate, $chargeDescription);
				}
				
				// Adjust the team donation credits
				if (!empty($changeInfo['teamCredits']))
				{
					$teamCredits = $changeInfo['teamCredits'];
					TDOTeamAccount::consumeTeamCreditsAfterPurchase($teamID, $teamCredits);
				}
				
				// Make sure that the renewal counts are cleared
				TDOTeamAccount::removeTeamFromAutorenewQueue($teamID);
			}
			
			// If the team subscription was cancelled and the team is in the
			// trial period, we need to have a way to get the credit card info
			// stored again.
			if (($teamSubscriptionStatus == TEAM_SUBSCRIPTION_STATE_TRIAL_PERIOD || $teamSubscriptionStatus == TEAM_SUBSCRIPTION_STATE_ACTIVE) && (!empty($stripeToken)))
			{
                $result = TDOTeamAccount::updateTeamBillingInfo($adminUserID, $teamID, $stripeToken, $link);
				if (!$result)
				{
					error_log("TDOTeamAccount::changeTeamAccount() unable to store credit card information for team ($teamID) admin ($adminUserID).");
                    return false;
                } elseif (isset($result['error']) && $result['error'] !== '') {
                    error_log("TDOTeamAccount::changeTeamAccount() unable to store credit card information for team ($teamID) admin ($adminUserID): " . $result['error']);
                    return array(
                        'success' => FALSE,
                        'error' => $result['error'],
                    );
				}
			}
			
			// If the code makes it this far, the admin has been successfully
			// charged for the change (if necessary) and all we need to do is
			// update the actual team account with all of the relevation info.
						
			if (!TDOTeamAccount::updateTeamAccountWithNewExpirationDate($teamID, $newExpirationDate, $billingFrequency))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::changeTeamAccount() could not extend the expiration date (newExpirationDate - " . date("d M Y", $newExpirationDate) . ") of a team ($teamID) that was just modified.");
				TDOMailer::sendTeamSubscriptionExpirationErrorNotification($teamID, $newExpirationDate);
			}
			
			// If the new number of members exceeds the original number of
			// members, we need to update the original license count to reflect
			// that the user just paid for an account.
			if ($numOfMembers > $origLicenseCount)
				$origLicenseCount = $numOfMembers;
			
			if (!TDOTeamAccount::updateTeamAccountWithLicenseCounts($teamID, $origLicenseCount, $numOfMembers))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::changeTeamAccount() could not update the license counts for a newly modified team ($teamID), licenses = $numOfMembers");
				TDOMailer::sendTeamSubscriptionLicenseCountErrorNotification($teamID, $numOfMembers);
			}
			
			return array("success" => true, "teamid" => $teamID);
		}
		
		
		public static function updateTeamBillingInfo($adminUserID, $teamID, $stripeToken, $link=NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::updateTeamBillingInfo() missing parameter adminUserID");
				return false;
			}
			
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::updateTeamBillingInfo() missing parameter teamID");
				return false;
			}
			
			if (!isset($stripeToken))
			{
				error_log("TDOTeamAccount::updateTeamBillingInfo() missing parameter stripeToken");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() could not get DB connection.");
					return false;
				}
			}
			
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::updateTeamBillingInfo() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			
			// If the admin user already has Stripe associated with his/her
			// account, update the credit card. Otherwise, create a new Stripe
			// customer account.
			$stripeCustomer = NULL;
			$adminEmail = TDOUser::usernameForUserId($adminUserID);
			$stripeCustomerID = TDOSubscription::getStripeCustomerID($adminUserID);
			
			if ($stripeCustomerID)
			{
				// The user has previously paid us using Stripe, so we need to
				// just update their credit card information with the
				// information that was just provided.
				try
				{
					$stripeCustomer = Stripe_Customer::retrieve($stripeCustomerID);
				}
				catch (Stripe_Error $e)
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() failed to call Stripe_Customer::retrieve() for Stripe customer ID ($stripeCustomerID) for admin user ($adminUserID)");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
                    return array("success" => false, "error" => _('The card was declined.'));
				}
				catch (Exception $e)
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() failed to retrieve the Stripe customer ($stripeCustomerID) for admin user ($adminUserID)");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
				
				$stripeCustomer->card = $stripeToken;
				if ($adminEmail && ((empty($stripeCustomer->email)) || ($adminEmail != $stripeCustomer->email)))
				{
					// Update the Stripe Customer email to match the admin
					$stripeCustomer->email = $adminEmail;
				}
				
				try
				{
					$stripeCustomer->save();
				}
				catch (Stripe_Error $e)
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() failed to call Stripe_Customer::save() for Stripe customer ID ($stripeCustomerID) for admin user ($adminUserID)");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
                    return array("success" => false, "error" => _('The card was declined.'));
				}
				catch (Exception $e)
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() failed to save the Stripe customer ($stripeCustomerID) for admin user ($adminUserID)");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			else
			{
				// Create a new stripe customer using the stripe token and save that
				// off to the admin user's account.
				$createParams = array(
									  "description" => $adminUserID,
									  "card" => $stripeToken
									  );
				if ($adminEmail)
				{
					$createParams["email"] = $adminEmail;
				}
				
				try
				{
					$stripeCustomer = Stripe_Customer::create($createParams);
				}
				catch (Stripe_Error $e)
				{
					$body = $e->getJsonBody();
					$err = $body['error'];
					
					$errString = "TDOTeamAccount::updateTeamBillingInfo() Stripe exception caught trying to create a new stripe customer for admin ($adminUserID) of the team ($teamID): " . $e->getHttpStatus();
					if (isset($err, $err['message']))
					{
						$errString .= ", message=" . $err['message'];
					}
					if (isset($err, $err['type']))
					{
						$errString .= ", type=" . $err['type'];
					}
					if (isset($err, $err['code']))
					{
						$errString .= ", code=" . $err['code'];
					}
					if (isset($err, $err['param']))
					{
						$errString .= ", param=" . $err['param'];
					}
					error_log($errString);
					
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
				catch (Exception $e)
				{
					$errString = "TDOTeamAccount::updateTeamBillingInfo() exception caught trying to create a new stripe customer for admin ($adminUserID) of the team ($teamID): " . $e->getMessage();
					error_log($errString);
					
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
				
				// Store the stripe customer
				$stripeCustomerID = $stripeCustomer->id;
				if (!TDOSubscription::storeStripeCustomer($adminUserID, $stripeCustomerID))
				{
					error_log("TDOTeamAccount::updateTeamBillingInfo() unable to store the stripe customer id for admin ($adminUserID) of the team ($teamID)");
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			// Set this admin as the billing user
			if (!TDOTeamAccount::setBillingUserForTeam($teamID, $adminUserID, $link))
			{
				error_log("TDOTeamAccount::updateTeamBillingInfo() unable to set the billing administrator for admin ($adminUserID) of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Email the admins of the team to let them know there is a new
			// billing administrator.
			$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamID, $link);
			if (!$teamAdminIDs)
			{
				// Should still return true since the billing admin was actually changed
				error_log("TDOTeamAccount::updateTeamBillingInfo() unable to determine the list of team administrators.");
			}
			
			$userEmail = TDOUser::usernameForUserId($adminUserID);
			$userDisplayName = TDOUser::displayNameForUserId($adminUserID);
			$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
			
			foreach($teamAdminIDs as $teamAdminUserID)
			{
				$adminEmail = TDOUser::usernameForUserId($teamAdminUserID);
				$adminDisplayName = TDOUser::displayNameForUserId($teamAdminUserID);
				
				TDOMailer::sendTeamNewBillingAdminNotification($teamID, $teamName, $userEmail, $userDisplayName, $adminEmail, $adminDisplayName);
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
				
		public static function getTaxRateInfoForZipCode($zipCode, $link=NULL)
		{
			if (!isset($zipCode))
				return false;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTaxRateForZipCode() could not get DB connection.");
					return false;
				}
			}
			
			$zipCode = mysql_real_escape_string($zipCode, $link);
			$sql = "SELECT taxrate,cityname FROM tdo_sales_tax WHERE zipcode='$zipCode'";
			if ($result = mysql_query($sql, $link))
			{
				if ($row = mysql_fetch_array($result))
				{
					$info = array(
								  'zipcode' => $zipCode,
								  'taxrate' => $row['taxrate'],
								  'cityname' => $row['cityname']
					);
					
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					
					return $info;
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return false;
		}
		
		public static function createTeamAccountWithTrial($adminUserID,
														  $stripeToken,
														  $numOfSubscriptions,
														  $billingFrequency,
														  $bizCountry,
														  $zipCode,
														  $teamName,
														  $bizName,
														  $bizPhone,
														  $bizAddr1,
														  $bizAddr2,
														  $bizCity,
														  $bizState,
														  $mainListID,
														  $discoveryAnswer)
		{
			// Create the new team
			if (!isset($adminUserID))
				return false;
			
			if (!isset($stripeToken))
				return false;
            $teams = TDOTeamAccount::getAdministeredTeamsForAdmin($adminUserID);
            if (!$teams) {
                $account = new TDOTeamAccount();
                $account->setBillingUserID($adminUserID);

                $subscriptionType = SUBSCRIPTION_TYPE_YEAR;
                if ($billingFrequency == "monthly") {
                    $subscriptionType = SUBSCRIPTION_TYPE_MONTH;
                }

                $account->setBillingFrequency($subscriptionType);
                if (isset($bizCountry))
                    $account->setBizCountry($bizCountry);
                if (isset($zipCode))
                    $account->setBizPostalCode($zipCode);
                if (isset($teamName))
                    $account->setTeamName($teamName);
                if (isset($bizName))
                    $account->setBizName($bizName);
                if (isset($bizPhone))
                    $account->setBizPhone($bizPhone);
                if (isset($bizAddr1))
                    $account->setBizAddr1($bizAddr1);
                if (isset($bizAddr2))
                    $account->setBizAddr2($bizAddr2);
                if (isset($bizCity))
                    $account->setBizCity($bizCity);
                if (isset($bizState))
                    $account->setBizState($bizState);
                if (isset($mainListID))
                    $account->setMainListID($mainListID);
                if (isset($discoveryAnswer))
                    $account->setDiscoveryAnswer($discoveryAnswer);

                if (!$account->addAccount()) {
                    error_log("TDOTeamAccount::createTeamAccountWithTrial() failed while calling TDOTeamAccount::addAccount()");
                    return array("success" => false, "error" => "Failed to add new account record.");
                }

                $teamID = $account->getTeamID();
            } else {
                if (is_array($teams)) {
                    $account = $teams[0];
                }
                $teamID = $account->getTeamID();
            }
			// Save off the Stripe Token so we can make a proper charge
			// when the auto-renewal date comes.
			
			$stripeCustomer = NULL;
			$createParams = array(
								  "description" => $adminUserID,
								  "card" => $stripeToken
								  );
			try
			{
				$stripeCustomer = Stripe_Customer::create($createParams);
			}
			catch (Stripe_Error $e)
			{
				$body = $e->getJsonBody();
				$err = $body['error'];
				
				$errString = "TDOTeamAccount::createTeamAccountWithTrial() Stripe exception caught trying to create a new stripe customer for admin ($adminUserID) of the team ($teamID): " . $e->getHttpStatus();
				if (isset($err, $err['message']))
				{
					$errString .= ", message=" . $err['message'];
				}
				if (isset($err, $err['type']))
				{
					$errString .= ", type=" . $err['type'];
				}
				if (isset($err, $err['code']))
				{
					$errString .= ", code=" . $err['code'];
				}
				if (isset($err, $err['param']))
				{
					$errString .= ", param=" . $err['param'];
				}
				error_log($errString);
                return array("success" => false, "error" => $err['message']);
			}
			catch (Exception $e)
			{
				$errString = "TDOTeamAccount::createTeamAccountWithTrial() exception caught trying to create a new stripe customer for admin ($adminUserID) of the team ($teamID): " . $e->getMessage();
				error_log($errString);
				return false;
			}
			
			// Store the stripe customer
			$stripeCustomerID = $stripeCustomer->id;
			if (!TDOSubscription::storeStripeCustomer($adminUserID, $stripeCustomerID))
			{
				error_log("TDOTeamAccount::createTeamAccountWithTrial() unable to store the stripe customer id for admin ($adminUserID) of the team ($teamID)");
				return false;
			}
			
			TDOSubscription::addOrUpdateUserPaymentSystemInfo($adminUserID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);
			
			// The expiration/renewal date should be NOW + Trial Period
			$now = new DateTime("now", new DateTimeZone("UTC"));
			$trialPeriodDateIntervalSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_TEAM_TRIAL_PERIOD_DATE_INTERVAL);
			$trialPeriodDateInterval = new DateInterval($trialPeriodDateIntervalSetting);
			$newExpirationDate = $now->add($trialPeriodDateInterval);
			$newExpirationTimestamp = $newExpirationDate->getTimestamp();

			if (!TDOTeamAccount::updateTeamAccountWithNewExpirationDate($teamID, $newExpirationTimestamp, $subscriptionType))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::createTeamAccountWithTrial() could not extend the expiration date (newExpirationTimestamp - " . date("d M Y", $newExpirationTimestamp) . ") of a team ($teamID) that was just created.");
				TDOMailer::sendTeamSubscriptionExpirationErrorNotification($teamID, $newExpirationTimestamp);
			}
			
			if (!TDOTeamAccount::updateTeamAccountWithLicenseCounts($teamID, $numOfSubscriptions, $numOfSubscriptions))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::createTeamAccountWithTrial() could not update the license counts for a newly purchased team ($teamID), licenses = $numOfSubscriptions");
				TDOMailer::sendTeamSubscriptionLicenseCountErrorNotification($teamID, $numOfSubscriptions);
			}
			
			// New in Version 2.4.x: Automatically add the team admin as a
			// member of the team.
			if (!TDOTeamAccount::addUserToTeam($adminUserID, $teamID, TEAM_MEMBERSHIP_TYPE_MEMBER, true))
			{
				error_log("TDOTeamAccount::createTeamAccountWithTrial() could not add a team admin as a member in a newly created team.");
			}
			
			// New in Version 2.4.x: Create a new list that's automatically
			// shared with every single team member.
			$list = new TDOList();
			
			// Set the List ID to the one we determined earlier when creating the team
			$list->setListId($mainListID);
			
			// Use the team name for the name of the team. If that doesn't exist,
			// use the business name. If that doesn't exist, use "Shared List".
			if (isset($teamName))
				$list->setName($teamName);
			else if (isset($bizName))
				$list->setName($bizName);
			else
				$list->setName(_('Shared List'));
			
			// The creator/owner of the shared list is the team.
			$list->setCreator($teamID);
			
			if (!$list->addList($adminUserID, $teamID))
			{
				error_log("TDOTeamAccount::createTeamAccountWithTrial() could not create a shared list for the team.");
				// TODO: Send error email about creating a team account
			}
			else
			{
				$listID = $list->listId();
                TDOTeamAccount::updateTeamMainList($adminUserID, $teamID, $listID);
				// Add the team admin as a list owner
				
				if (!TDOList::shareWithUser($listID, $adminUserID, LIST_MEMBERSHIP_OWNER))
				{
					error_log("TDOTeamAccount::createTeamAccountWithTrial() unable to add the list admin as an owner of the newly created shared list.");
				}
			}
			
			if (TDO_SERVER_TYPE == "production" || TDO_SERVER_TYPE == "beta" || TDO_SERVER_TYPE == "auth")
			{
				// Send an email to business@appigo.com to inform our team about the
				// creation of a brand new Todo for Business team.
				TDOMailer::sendTeamCreatedNotification($teamID);
			}
			
			return array("success" => true, "teamid" => $teamID);
		}
		
		
		
		//
		// If successful, return a new team object
		//
		public static function createAndPurchaseTeamAccount($adminUserID,
															$stripeToken,
															$numOfSubscriptions,
															$billingFrequency,
															$bizCountry,
															$zipCode,
															$totalPrice,
															$teamName,
															$bizName,
															$bizPhone,
															$bizAddr1,
															$bizAddr2,
															$bizCity,
															$bizState,
															$mainListID=NULL,
															$discoveryAnswer=NULL)
		{
			// Create the new team
			if (!isset($adminUserID))
				return false;
			
			$account = new TDOTeamAccount();
			$account->setBillingUserID($adminUserID);
			
            $chargeDescription = sprintf(_('Todo Cloud Team Account - %s member(s), 1 yr'), $numOfSubscriptions);
			$subscriptionType = SUBSCRIPTION_TYPE_YEAR;
			if ($billingFrequency == "monthly")
			{
                $chargeDescription = sprintf(_('Todo Cloud Team Account - %s member(s), 1 mo'), $numOfSubscriptions);
                $subscriptionType = SUBSCRIPTION_TYPE_MONTH;
			}
			
			$account->setBillingFrequency($subscriptionType);
			if (isset($bizCountry))
				$account->setBizCountry($bizCountry);
			if (isset($zipCode))
				$account->setBizPostalCode($zipCode);
			if (isset($teamName))
				$account->setTeamName($teamName);
			if (isset($bizName))
				$account->setBizName($bizName);
			if (isset($bizPhone))
				$account->setBizPhone($bizPhone);
			if (isset($bizAddr1))
				$account->setBizAddr1($bizAddr1);
			if (isset($bizAddr2))
				$account->setBizAddr2($bizAddr2);
			if (isset($bizCity))
				$account->setBizCity($bizCity);
			if (isset($bizState))
				$account->setBizState($bizState);
			if (isset($mainListID))
				$account->setMainListID($mainListID);
			if (isset($discoveryAnswer))
				$account->setDiscoveryAnswer($discoveryAnswer);
			
			if (!$account->addAccount())
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() failed while calling TDOTeamAccount::addAccount()");
				return array("success" => false, "error" => "Failed to add new account record.");
			}
			
			$teamID = $account->getTeamID();
			
			//
			// Calculate the actual price to charge. We can verify against the
			// totalPrice, but don't use that as the authoritative price.
			//($billingFrequency, $numberOfSubscriptions, $zipCode, $teamAdminUserID=NULL, $teamID=NULL, $link=NULL)
			$teamPricingInfo = TDOTeamAccount::getTeamPricingInfo($billingFrequency, $numOfSubscriptions, false, $adminUserID, $teamID);
			if (!$teamPricingInfo)
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not determine team pricing info for team purchase ($teamID) of $numOfSubscriptions additional licenses.");
				TDOTeamAccount::deleteTeamAccount($teamID);
				return array("success" => false, "error" => "Unable to determine pricing information.");
			}
			
			// $teamPricingInfo should contain the following:
			//	billingFrequency: monthly | yearly
			//	unitPrice: the price of 1 monthly or yearly item
			//	unitCombinedPrice: unitPrice x number of subscriptions
			//	numOfSubscriptions: the number of subscriptions being purchased
			//	discountPercentage: 0, 5, 10, or 20 (for 0%, 5%, 10%, or 20%)
			//	discountAmount: a dollar amount for the discount (not a negative number, so use accordingly)
			//	subtotal: unitCombinedPrice less any discount
			//	totalPrice
			
			$unitPriceInCents = $teamPricingInfo['unitPrice'] * 100;
			$unitCombinedPriceInCents = $teamPricingInfo['unitCombinedPrice'] * 100;
			$subtotalInCents = $teamPricingInfo['subtotal'] * 100;
			$discountPercentage = $teamPricingInfo['discountPercentage'];
			$discountInCents = $teamPricingInfo['discountAmount'] * 100;
			$totalInCents = $teamPricingInfo['totalPrice'] * 100;
			
			// Determine the expiration date
			$expirationDate = new DateTime('@' . $account->getExpirationDate(), new DateTimeZone("UTC"));
			$now = new DateTime("now", new DateTimeZone("UTC"));
			$timeToAdd = NULL;
			if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
			{
				$oneYearPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
				$timeToAdd = new DateInterval($oneYearPeriodSetting);
			}
			else
			{
				$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
				$timeToAdd = new DateInterval($oneMonthPeriodSetting);
			}
			
			$newExpirationDate = NULL;
			if ($newExpirationDate < $now)
			{
				$newExpirationDate = $now->add($timeToAdd);
			}
			else
			{
				$newExpirationDate = $expirationDate->add($timeToAdd);
			}
			$newExpirationTimestamp = $newExpirationDate->getTimestamp();
			
			
//			error_log("STUFF: $adminUserID, $teamID, $stripeToken, NULL, $subtotalInCents, $discountPercentage, $discountInCents, $chargeDescription, $subscriptionType, false, $numOfSubscriptions");
			
			// Process the payment
			$stripeCharge = TDOSubscription::makeStripeCharge($adminUserID,
															  $teamID,
															  $stripeToken,
															  NULL, // last4 - since we're using stripeToken, this isn't needed
															  $unitPriceInCents,
															  $unitCombinedPriceInCents,
															  $subtotalInCents,
															  $discountPercentage,
															  $discountInCents,
															  0, // teamCreditMonths
															  0, // teamCreditsPriceDiscountInCents
															  $totalInCents,
															  $chargeDescription,
															  $subscriptionType,
															  $newExpirationTimestamp,
															  false, // $isGiftCodePurchase
															  $numOfSubscriptions);
			
			if (empty($stripeCharge) || isset($stripeCharge['errcode']))
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not complete the team ($teamID) charge with Stripe. Error code = " . $stripeCharge['errcode'] . ", Error description = " . $stripeCharge['errdesc']);
				TDOTeamAccount::deleteTeamAccount($teamID);
				return array("success" => false, "error" => $stripeCharge['stripe_error_message']);
			}
			
			// If the payment is successful, update the team with the proper
			// number of subscriptions
//			$account->setExpirationDate($newExpirationTimestamp);
//			$account->setLicenseCount($numOfSubscriptions);
//			$account->setNewLicenseCount($numOfSubscriptions);
			
			
			// Keep a record of the charge
			$nowTimestamp = time();
			$stripeCustomerID = NULL;
			if (isset($stripeCharge['customer']))
				$stripeCustomerID = $stripeCharge['customer'];
			else
				$stripeCustomerID = TDOSubscription::getStripeCustomerID($adminUserID);

			if (empty($stripeCustomerID))
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() failed to retrieve admin ($adminUserID) stripeCustomerID for for team ($teamID)");
				
				// Email the support team to let them know a charge was successful
				// but for whatever reason, we weren't able to find a Stripe
				// Customer ID, which means we could not log the purchase.
				TDOMailer::sendSubscriptionLogErrorNotification($teamID);
			}
			else
			{
				$cardType = 'N/A';
				$last4 = 'XXXX';
				if (isset($stripeCharge->card))
				{
					$card = $stripeCharge->card;
					if (isset($card['type']))
						$cardType = $card['type'];
					if (isset($card['last4']))
						$last4 = $card['last4'];
				}
				
				TDOSubscription::logStripePayment($adminUserID, $teamID, $numOfSubscriptions, $stripeCustomerID, $stripeCharge->id, $cardType, $last4, $subscriptionType, $stripeCharge->amount, $nowTimestamp, $chargeDescription);
				TDOSubscription::addOrUpdateUserPaymentSystemInfo($adminUserID, PAYMENT_SYSTEM_TYPE_STRIPE, $stripeCustomerID);
			}
			
			if (!TDOTeamAccount::updateTeamAccountWithNewExpirationDate($teamID, $newExpirationTimestamp, $subscriptionType))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not extend the expiration date (newExpirationTimestamp - " . date("d M Y", $newExpirationTimestamp) . ") of a team ($teamID) that was just created and purchased.");
				TDOMailer::sendTeamSubscriptionExpirationErrorNotification($teamID, $newExpirationTimestamp);
			}
			
			if (!TDOTeamAccount::updateTeamAccountWithLicenseCounts($teamID, $numOfSubscriptions, $numOfSubscriptions))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not update the license counts for a newly purchased team ($teamID), licenses = $numOfSubscriptions");
				TDOMailer::sendTeamSubscriptionLicenseCountErrorNotification($teamID, $numOfSubscriptions);
			}
			
			// New in Version 2.4.x: Automatically add the team admin as a
			// member of the team.
			if (!TDOTeamAccount::addUserToTeam($adminUserID, $teamID, TEAM_MEMBERSHIP_TYPE_MEMBER, true))
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not add a team admin as a member in a newly created team.");
			}
			
			// New in Version 2.4.x: Create a new list that's automatically
			// shared with every single team member.
			$list = new TDOList();
			$list->setListId($mainListID);
			
			
			// Use the team name for the name of the team. If that doesn't exist,
			// use the business name. If that doesn't exist, use "Shared List".
			if (isset($teamName))
				$list->setName($teamName);
			else if (isset($bizName))
				$list->setName($bizName);
			else
				$list->setName(_('Shared List'));
			
			// The creator/owner of the shared list is the team.
			$list->setCreator($teamID);
			
			if (!$list->addList($adminUserID, $teamID))
			{
				error_log("TDOTeamAccount::createAndPurchaseTeamAccount() could not create a shared list for the team.");
				// TODO: Send error email about creating a team account
			}
			else
			{
				$listID = $list->listId();
                TDOTeamAccount::updateTeamMainList($adminUserID, $teamID, $listID);
                // Add the team admin as a list owner
				
				if (!TDOList::shareWithUser($listID, $adminUserID, LIST_MEMBERSHIP_OWNER))
				{
					error_log("TDOTeamAccount::createAndPurchaseTeamAccount() unable to add the list admin as an owner of the newly created shared list.");
				}
			}
			
			return array("success" => true, "teamid" => $teamID);
		}
		
		
		public static function updateTeamAccountWithNewExpirationDate($teamID, $newExpirationTimestamp, $billingFrequency = SUBSCRIPTION_TYPE_UNKNOWN, $link = NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() could not get DB connection.");
					return false;
				}
			}
			
			// Do this in a transaction so that we can update the
			// tdo_team_accounts table AND every member of the team with the
			// new expiration date.
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() couldn't start a transaction: " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$now = time();
			$sql = "UPDATE tdo_team_accounts SET expiration_date=$newExpirationTimestamp,billing_frequency=$billingFrequency,modified_date=$now WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() unable to update the expiration date ($newExpirationTimestamp - " . date('d M Y', $newExpirationTimestamp) . ") for team ($teamID): " . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			
			// Update the expiration dates of all of the team members
			$memberSubscriptionIDs = TDOTeamAccount::getTeamMemberSubscriptionIDs($teamID, $link);
			if ($memberSubscriptionIDs)
			{
				foreach($memberSubscriptionIDs as $subscriptionID)
				{
					// Skip over members that are currently Apple or GooglePlay
					// IAP customers because we are obligated to not touch their
					// expiration dates.
					
					$userID = TDOSubscription::getUserIDForSubscriptionID($subscriptionID);
					if (!TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID))
					{
						if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $billingFrequency, SUBSCRIPTION_LEVEL_TEAM, $teamID, $link))
						{
							// An error occurred!
							error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() couldn't update a team ($teamID) member's subscription ($subscriptionID) expiration date ($newExpirationTimestamp - " . date('d M Y', $newExpirationTimestamp) . ")");
							mysql_query("ROLLBACK", $link);
							if($closeLink)
								TDOUtil::closeDBLink($link);
							return false;
						}
					}
				}
			}
			
			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithNewExpirationDate() couldn't commit transaction after updating the expiration date ($newExpirationTimestamp - " . date('d M Y', $newExpirationTimestamp) . "for team ($teamID)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return true;
		}
		
		
		//
		// currentLicenseCount
		//	This specifies how many subscriptions should currently be active
		//	during the current billing period.
		// newLicenseCount
		//	This specifies what the license count should be during the next
		//	renewal period. There are really only 2 valid values for this:
		//		1)	newLicenseCount will match EXACTLY what currentLicenseCount
		//			is. This is the most common case during a normal renewal.
		//		2)	newLicenseCount will be LESS than currentLicenseCount
		//			because an admin has downgraded how many licenses they need,
		//			but it won't kick in until the next billing cycle.
		public static function updateTeamAccountWithLicenseCounts($teamID, $currentLicenseCount, $newLicenseCount, $link = NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithLicenseCounts() failed because teamID is empty");
				return false;
			}
			
			if (($currentLicenseCount < 0) || ($newLicenseCount < 0))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithLicenseCounts() failed because currentLicenseCount or newLicenseCount are specified as less than 0");
				return false;
			}
			
			if ($newLicenseCount > $currentLicenseCount)
			{
				error_log("TDOTeamAccount::updateTeamAccountWithLicenseCounts() failed because newLicenseCount cannot be greater than currentLicenseCount. This condition should never exist. If an admin adds more licenses to the team, they pay for that immediately, not during an auto-renewal.");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamAccountWithLicenseCounts() could not get DB connection.");
					return false;
				}
			}
			
			$modifiedDate = time();
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "UPDATE tdo_team_accounts SET license_count=$currentLicenseCount,new_license_count=$newLicenseCount,modified_date=$modifiedDate WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateTeamAccountWithLicenseCounts() unable to set the new license counts for team ($teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		public static function updateTeamName($adminUserID, $teamID, $newTeamName, $link = NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::updateTeamName() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::updateTeamName() failed because teamID is empty");
				return false;
			}
			if ((!isset($newTeamName)) || (strlen(trim($newTeamName)) == 0))
			{
				error_log("TDOTeamAccount::updateTeamName() failed because newTeamName is empty");
				return false;
			}
			$newTeamName = trim($newTeamName);
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamName() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::updateTeamName() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$adminUserID = mysql_real_escape_string($adminUserID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$newTeamName = mysql_real_escape_string($newTeamName, $link);
			
			$modifiedDate = time();
			
			$sql = "UPDATE tdo_team_accounts SET teamname='$newTeamName',modified_date=$modifiedDate WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateTeamName() unable to set a new team name for team ($teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}

        public static function updateTeamMainList($adminUserID, $teamID, $mainListId, $link = NULL)
        {
            if (!isset($adminUserID))
            {
                error_log("TDOTeamAccount::updateTeamMainList() failed because adminUserID is empty");
                return false;
            }
            if (!isset($teamID))
            {
                error_log("TDOTeamAccount::updateTeamMainList() failed because teamID is empty");
                return false;
            }
            if ((!isset($mainListId)) || (strlen(trim($mainListId)) == 0))
            {
                error_log("TDOTeamAccount::updateTeamMainList() failed because mainListId is empty");
                return false;
            }

            $closeLink = false;
            if ($link == NULL)
            {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if (!$link)
                {
                    error_log("TDOTeamAccount::updateTeamMainList() could not get DB connection.");
                    return false;
                }
            }

            // Check to see if the adminUserID is indeed a team administrator.
            // If they are not, do not allow this to happen.
            if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
            {
                error_log("TDOTeamAccount::updateTeamMainList() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }

            $teamID = mysql_real_escape_string($teamID, $link);
            $mainListId = mysql_real_escape_string(trim($mainListId), $link);

            $modifiedDate = time();

            $sql = "UPDATE tdo_team_accounts SET main_listid='$mainListId',modified_date=$modifiedDate WHERE teamid='$teamID'";
            if (!mysql_query($sql, $link))
            {
                error_log("TDOTeamAccount::updateTeamMainList() unable to set a new main listid for team ($teamID): " . mysql_error());
                if($closeLink)
                    TDOUtil::closeDBLink($link);
                return false;
            }

            if ($closeLink)
                TDOUtil::closeDBLink($link);
            return true;
        }
		
		public static function updateTeamInfo($adminUserID, $teamID, $bizName=NULL, $bizPhone=NULL, $bizAddr1=NULL, $bizAddr2=NULL, $bizCity=NULL, $bizState=NULL, $bizCountry=NULL, $bizPostalCode=NULL, $link = NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::updateTeamInfo() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::updateTeamInfo() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::updateTeamInfo() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::updateTeamInfo() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$adminUserID = mysql_real_escape_string($adminUserID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "UPDATE tdo_team_accounts SET ";
			
			$insertComma = false;
			if (!empty($bizName))
			{
				$bizName = trim($bizName);
				$bizName = mysql_real_escape_string($bizName, $link);
				$sql .= " biz_name='$bizName' ";
				$insertComma = true;
			}
			if (!empty($bizPhone))
			{
				$bizPhone = trim($bizPhone);
				$bizPhone = mysql_real_escape_string($bizPhone, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_phone='$bizPhone' ";
				$insertComma = true;
			}
			if (!empty($bizAddr1))
			{
				$bizAddr1 = trim($bizAddr1);
				$bizAddr1 = mysql_real_escape_string($bizAddr1, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_addr1='$bizAddr1' ";
				$insertComma = true;
			}
			if (!empty($bizAddr2))
			{
				$bizAddr2 = trim($bizAddr2);
				$bizAddr2 = mysql_real_escape_string($bizAddr2, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_addr2='$bizAddr2' ";
				$insertComma = true;
			}
			if (!empty($bizCity))
			{
				$bizCity = trim($bizCity);
				$bizCity = mysql_real_escape_string($bizCity, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_city='$bizCity' ";
				$insertComma = true;
			}
			if (!empty($bizState))
			{
				$bizState = trim($bizState);
				$bizState = mysql_real_escape_string($bizState, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_state='$bizState' ";
				$insertComma = true;
			}
			if (!empty($bizCountry))
			{
				$bizCountry = trim($bizCountry);
				$bizCountry = mysql_real_escape_string($bizCountry, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_country='$bizCountry' ";
				$insertComma = true;
			}
			if (!empty($bizPostalCode))
			{
				$bizPostalCode = trim($bizPostalCode);
				$bizPostalCode = mysql_real_escape_string($bizPostalCode, $link);
				if ($insertComma)
					$sql .= ",";
				$sql .= " biz_postal_code='$bizPostalCode' ";
				$insertComma = true;
			}
			
			if (!$insertComma)
			{
				// If we don't need to insert a comma, that means there's no
				// need to actually update anything because nothing actually
				// changed. So just return true.
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return true;
			}
			
			$modifiedDate = time();
			$sql .= ",modified_date=$modifiedDate";
			
			$sql .= " WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateTeamName() unable to set a new team name for team ($teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		public static function getAdminUserIDsForTeam($teamID, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getAdminUserIDsForTeam() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getAdminUserIDsForTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT userid FROM tdo_team_admins WHERE teamid='$teamID'";
			
			if ($result = mysql_query($sql, $link))
			{
				$userids = array();
				while ($row = mysql_fetch_array($result))
				{
					if (!empty($row['userid']))
					{
						$userid = $row['userid'];
						$userids[] = $userid;
					}
				}
				
				if($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $userids;
			}
			
			error_log("TDOTeamAccount::getAdminUserIDsForTeam() failed to make the query for the team ($teamID): " . mysql_error());
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function getUserIDsForTeam($teamID, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getUserIDsForTeam() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getUserIDsForTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT userid FROM tdo_team_members WHERE teamid='$teamID'";
			
			if ($result = mysql_query($sql, $link))
			{
				$userids = array();
				while ($row = mysql_fetch_array($result))
				{
					if (!empty($row['userid']))
					{
						$userid = $row['userid'];
						$userids[] = $userid;
					}
				}
				
				if($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $userids;
			}
			
			error_log("TDOTeamAccount::getUserIDsForTeam() failed to make the query for the team ($teamID): " . mysql_error());
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		// Returns an alphabetized (by display name or first name) array
		// containing 'userid', 'displayName', and 'userName' for every team
		// member which can be used when showing team license slots.
		// Requires the requester to be a system or team admin
		public static function getTeamMemberInfo($adminUserID, $teamID, $link=NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::getTeamMemberInfo() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getTeamMemberInfo() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamMemberInfo() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::getTeamMemberInfo() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$teamUserIDs = TDOTeamAccount::getUserIDsForTeam($teamID, $link);
			if (!$teamUserIDs)
			{
				error_log("TDOTeamAccount::getTeamMemberInfo() failed to read the team ($teamID) members");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$userInfos = array();
			foreach ($teamUserIDs as $userid)
			{
				$userInfo = TDOUser::displayInfoForUserId($userid);
				if (!$userInfo)
					continue;
				
				// Add in the userid
				$userInfo['userid'] = $userid;
				
				$userInfos[] = $userInfo;
			}
			
			// Sort the array alphabetically by displayName
			$userInfos = TDOUtil::arrayRecordSort($userInfos, "displayName");
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return $userInfos;
		}
		
		
		public static function teamNameForTeamID($teamID, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::teamNameForTeamID() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::teamNameForTeamID() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT teamname FROM tdo_team_accounts WHERE teamid='$teamID'";
			if ($result = mysql_query($sql, $link))
			{
				if ($row = mysql_fetch_array($result))
				{
					$teamName = $row['teamname'];
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $teamName;
				}
			}
			else
			{
				error_log("TDOTeamAccount::teamNameForTeamID($teamID) had a failure reading from the DB: " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		// Checks to see if the specified email is already a member of the team.
		// This is useful when used with inviting a new person (so you don't end
		// up inviting the same people more than once).
		public static function isEmailMemberOfTeam($email, $teamID, $membershipType, $link=NULL)
		{
			if (!isset($email))
			{
				error_log("TDOTeamAccount::isEmailMemberOfTeam() failed because email is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::isEmailMemberOfTeam() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isEmailMemberOfTeam() could not get DB connection.");
					return false;
				}
			}
			
			$email = strtolower($email);
			$email = mysql_real_escape_string($email, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$tableName = "tdo_team_members";
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
				$tableName = "tdo_team_admins";
			
			$sql = "SELECT username FROM $tableName LEFT JOIN tdo_user_accounts ON $tableName.userid=tdo_user_accounts.userid WHERE teamid='$teamID' AND username='$email'";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['username']))
				{
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return true;
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}

        public static function isMemberOfTeam($userID, $teamID, $membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER, $link = NULL)
        {
            if (!isset($userID)) {
                error_log("TDOTeamAccount::isMemberOfTeam() failed because email is empty");
                return false;
            }
            if (!isset($teamID)) {
                error_log("TDOTeamAccount::isMemberOfTeam() failed because teamID is empty");
                return false;
            }

            $closeLink = false;
            if ($link == NULL) {
                $closeLink = true;
                $link = TDOUtil::getDBLink();
                if (!$link) {
                    error_log("TDOTeamAccount::isMemberOfTeam() could not get DB connection.");
                    return false;
                }
            }

            $userID = mysql_real_escape_string($userID, $link);
            $teamID = mysql_real_escape_string($teamID, $link);
            $tableName = "tdo_team_members";
            if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN) {
                $tableName = "tdo_team_admins";
            }

            $sql = "SELECT userid FROM $tableName WHERE teamid='$teamID' AND userid='$userID'";
            if ($result = mysql_query($sql, $link)) {
                $row = mysql_fetch_array($result);
                if ($row && mysql_num_rows($result) && isset($row['userid'])) {
                    if ($closeLink)
                        TDOUtil::closeDBLink($link);
                    return true;
                }
            }
            if ($closeLink)
                TDOUtil::closeDBLink($link);
            return false;
        }

        public static function addUserToTeam($userID, $teamID, $membershipType, $donateSubscriptionToTeam=false, $link=NULL)
		{
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::addUserToTeam() failed because userID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::addUserToTeam() failed because teamID is empty");
				return false;
			}
            $teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);

            $closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::addUserToTeam() could not get DB connection.");
					return false;
				}
			}
			$userID = mysql_real_escape_string($userID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$tableName = "tdo_team_members";
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
				$tableName = "tdo_team_admins";
			$sql = "INSERT INTO $tableName (teamid, userid) VALUES ('$teamID', '$userID')";
			$response = mysql_query($sql, $link);
			if (!$response)
			{
				// Both of these tables enforce uniqueness. If this is the error
				// we get, ignore it because the userid already exists.
				if (mysql_errno($link) != 1062) // duplicate key (already exist in table)
				{
					error_log("TDOTeamAccount::addUserToTeam() failed (teamID: $teamID, userID: $userID): " . mysql_error());
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			$userSubscription = TDOSubscription::getSubscriptionForUserID($userID, $link);
			if (!$userSubscription)
			{
				error_log("TDOTeamAccount::addUserToTeam() couldn't determine a valid subscription for the user ($userID).");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$iapUser = false;
			$remainingPersonalSubscriptionMonthsLeft = 0;
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				$subscriptionID = $userSubscription->getSubscriptionID();
				
				// As of Todo Cloud Web 2.4 we allow subscriptions paid with App
				// Store IAP or GooglePlay auto-renewing subscriptions to join
				// Todo for Business teams so they can participate. We just need to
				// send them an instructional email to indicate how they can cancel
				// their auto-renewing personal subscription so their Todo Cloud
				// subscription can be paid for by the team (their personal
				// subscription has to be canceled before the next renewal date).
				if (TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userID))
				{
					$iapUser = true;
					
					// Mark the user's susbcription account as belonging to a
					// team so that the service can pass on the information to
					// remote clients.
					if (!TDOSubscription::setTeamForSubscription($subscriptionID, $teamID, $link))
					{
						error_log("TDOTeamAccount::addUserToTeam() couldn't mark a subscription ($subscriptionID) with a team ID ($teamID) for the user ($userID).");
						if($closeLink)
							TDOUtil::closeDBLink($link);
						return false;
					}
				}
				else
				{
					//
					// 2. Extend the user's subscription
					//
					
					// Before we change the user's subscription, check to see
					// how many month(s) they have remaining so we can give
					// proper credit later, but only if what they have left is
					// greater than 14 days.
					$now = time();
					$forteenDaysFromNow = $now + 1209600;
					$expirationDate = $userSubscription->getExpirationDate();
					if ($expirationDate > $forteenDaysFromNow)
					{
						// There are more than 14 days left, so figure out how
						// many months by dividing by 30 days, generously
						// rounding up.
						$timeLeftInSeconds = $expirationDate - $now;
						$remainingPersonalSubscriptionMonthsLeft = ceil($timeLeftInSeconds / 2592000);
					}

					$newExpirationTimestamp = $teamAccount->getExpirationDate();
					$billingFrequency = $teamAccount->getBillingFrequency();
					
					if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $billingFrequency, SUBSCRIPTION_LEVEL_TEAM, $teamID, $link))
					{
						error_log("TDOTeamAccount::addUserToTeam() couldn't update a subscription ($subscriptionID) for the user ($userID), from the teamID ($teamID).");
						if($closeLink)
							TDOUtil::closeDBLink($link);
						return false;
					}
				}
			}
			
			if ($iapUser == true)
			{
				// 4. For IAP Users: Email instructional email about how to cancel
				//    their auto-renewing IAP
				
				$teamName = $teamAccount->getTeamName();
				
				$nextRenewalDate = $userSubscription->getExpirationDate();
				
				if (TDOInAppPurchase::userIsAppleIAPUser($userID))
				{
					// Send the instructions to the user about how to cancel
					// the App Store IAP auto-renewing subscription.
					TDOMailer::sendTeamMemberIAPCancellationInstructions($userID, $nextRenewalDate, $teamName, true, "apple");
				}
				else if (TDOInAppPurchase::userIsGooglePlayUser($userID))
				{
					// Send instructions to the user about how to cancel the
					// GooglePlay auto-renewing subscription.
					TDOMailer::sendTeamMemberIAPCancellationInstructions($userID, $nextRenewalDate, $teamName, true, "google");
				}
			}
			else
			{
				// 5. For NON-IAP Users: Determine the appropriate action if their
				//    personal account still has remaining subscription time
				
				// Determine if there are remaining months on the user's
				// personal account.
				if ($remainingPersonalSubscriptionMonthsLeft > 0)
				{
					if ($donateSubscriptionToTeam == true)
					{
						if (!TDOTeamAccount::saveSubscriptionMonthsToTeamCredit($teamID, $userID, $remainingPersonalSubscriptionMonthsLeft, false, $link))
						{
							error_log("TDOTeamAccount::addUserToTeam() couldn't save personal subscription information to the team credit storage. User ID: " . $userID . ", Team ID: " . $teamID);
						}
					}
					else
					{
						// Convert the remaining time to a promo code and send the
						// promo code to the user.
						$promoCodeNote = "Converted personal subscription of " . $remainingPersonalSubscriptionMonthsLeft . " months to a promo code while joining a team.";
						$newPromoCode = TDOPromoCode::createPromoCode($remainingPersonalSubscriptionMonthsLeft, $userID, $userID, $promoCodeNote);
						
						if ($newPromoCode && !empty($newPromoCode['success']) && $newPromoCode['success'])
						{
							$promoLink = $newPromoCode['promolink'];
							$teamName = $teamAccount->getTeamName();
							TDOMailer::sendPromoCodeToNewTeamMember($userID, $promoLink, $remainingPersonalSubscriptionMonthsLeft, $teamName);
						}
					}
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return true; // Person joined the team successfully!
		}
		
		
		public static function deleteUserFromTeam($userID, $teamID, $membershipType, $link=NULL)
		{
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::deleteUserFromTeam() failed because userID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::deleteUserFromTeam() failed because teamID is empty");
				return false;
			}
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::deleteUserFromTeam() could not get DB connection.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$tableName = "tdo_team_members";
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_ADMIN)
				$tableName = "tdo_team_admins";
			$sql = "DELETE FROM $tableName WHERE teamid='$teamID' AND userid='$userID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::deleteUserFromTeam() delete from the $tableName table (userID: $userID, teamID: $teamID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		// Returns an array of strings which should be presented to users when
		// they create a new Todo for Business team.
		public static function getPossibleDiscoveryAnswers()
		{
			$possibleDiscoveryAnswers = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_DISCOVERY_ANSWERS', DEFAULT_SYSTEM_SETTING_DISCOVERY_ANSWERS);
			if (empty($possibleDiscoveryAnswers))
				return array();
			
			$trimmedArray = array_map('trim', explode(",", $possibleDiscoveryAnswers));
			return $trimmedArray;
		}
		
		
		//
		// ------------------------ MEMBER INVITATIONS ------------------------
		//
		
		
		// Returns an array with the following key names for each record:
		//	invitationid
		//	email
		// Sorted by email address
		// Requires adminUserID to be a team administrator
		public static function getTeamInvitationInfo($adminUserID, $teamID, $membershipType=TEAM_MEMBERSHIP_TYPE_MEMBER, $link=NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::getTeamInvitationInfo() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getTeamInvitationInfo() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamInvitationInfo() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::getTeamInvitationInfo() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$sql = "SELECT invitationid,email,timestamp FROM tdo_team_invitations WHERE teamid='$teamID' AND membership_type=$membershipType ORDER BY email";
			
			if ($result = mysql_query($sql, $link))
			{
				$invitations = array();
				while ($row = mysql_fetch_array($result))
				{
					if (!empty($row['invitationid']))
					{
						$invitationID = $row['invitationid'];
						$email = $row['email'];
						$timestamp = $row['timestamp'];

						$invitation = array(
											"invitationid" => $invitationID,
											"email" => $email,
											"timestamp" => $timestamp,
											);
						$invitations[] = $invitation;
					}
				}
				
				if($closeLink)
					TDOUtil::closeDBLink($link);
				
				return $invitations;
			}
			
			error_log("TDOTeamAccount::getTeamInvitationInfo() failed to make the query for the team ($teamID): " . mysql_error());
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		public static function getInvitationCountByTeamId($team_id){
            if(!isset($team_id))
                return false;

            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTeamAccount::getTeamInvitationInfo failed to get dblink");
                return false;
            }

            $$team_id = mysql_real_escape_string($team_id, $link);

            $sql = "SELECT COUNT(*) from tdo_team_invitations WHERE teamid='$team_id'";
            $result = mysql_query($sql);
            if($result)
            {
                $total = mysql_fetch_array($result);
                if($total && isset($total[0]))
                {
                    TDOUtil::closeDBLink($link);
                    return $total[0];
                }
            }

            TDOUtil::closeDBLink($link);
            return false;
        }

		public static function invitationInfoForInvitationID($invitationID, $link=NULL)
		{
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::invitationInfoForInvitationID() failed because invitationID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::invitationInfoForInvitationID() could not get DB connection.");
					return false;
				}
			}
			
			$invitationID = mysql_real_escape_string($invitationID, $link);
			$sql = "SELECT userid,teamid,email,membership_type,invited_userid FROM tdo_team_invitations WHERE invitationid='$invitationID'";
			if ($result = mysql_query($sql, $link))
			{
				$row = mysql_fetch_array($result);
				if ($row && isset($row['userid']))
				{
					$invitationInfo = array(
											"invitationid" => $invitationID,
											"userid" => $row['userid'],
											"teamid" => $row['teamid'],
											"invited_userid" => $row['invited_userid'],
											"email" => $row['email'],
											"membershipType" => $row['membership_type']
											);
					
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $invitationInfo;
				}
			}
			error_log("TDOTeamAccount::invitationInfoForInvitationID() failed to make the query for the invitationID ($invitationID): " . mysql_error());
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function inviteTeamMember($adminUserID, $teamID, $email, $membershipType=TEAM_MEMBERSHIP_TYPE_MEMBER, $link=NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::inviteTeamMember() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::inviteTeamMember() failed because teamID is empty");
				return false;
			}
			if (!isset($email))
			{
				error_log("TDOTeamAccount::inviteTeamMember() failed because email is empty");
				return false;
			}
			
			$email = trim($email);
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::inviteTeamMember() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::inviteTeamMember() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Check to see if the email matches someone who is already a
			// member of the team. If it does, don't waste the energy to invite
			// them again (because that would be plain stupid).
			if (TDOTeamAccount::isEmailMemberOfTeam($email, $teamID, $membershipType, $link))
			{
				error_log("TDOTeamAccount::inviteTeamMember(teamID: $teamID) called with an email ($email) that is already an admin/member of the team.");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// If an invitation already exists for the specified email address,
			// don't create a new invitation.
			$invitationID = TDOTeamAccount::teamInvitationIDForEmail($teamID, $email, $membershipType, $link);
			if ($invitationID)
			{
				error_log("TDOTeamAccount::inviteTeamMember() was called for an email ($email) that has already been invited to this team ($teamID)");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$invitationInfo = TDOTeamAccount::createTeamInvitation($adminUserID, $teamID, $email, $membershipType, $link);
			if (!$invitationInfo)
			{
				error_log("TDOTeamAccount::inviteTeamMember() failed calling createTeamInvitation.");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Send the invitation to the user at the specified email address
			if (!TDOTeamAccount::sendTeamInvitation($adminUserID, $invitationInfo['invitationid'], $invitationInfo['email'], $membershipType, $link))
			{
				// Not necessarily a fatal error since the admin should be able
				// to click on the resend link. Still kinda bad though, so we'll
				// at least log it.
				error_log("TDOTeamAccount::inviteTeamMember() failed to send an email invitation for the team ($teamID) to email: $email");
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return $invitationInfo;
		}
		
		
		public static function teamInvitationIDForEmail($teamID, $email, $membershipType=TEAM_MEMBERSHIP_TYPE_MEMBER, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::teamInvitationIDForEmail() failed because teamID is empty");
				return false;
			}
			if (!isset($email))
			{
				error_log("TDOTeamAccount::teamInvitationIDForEmail() failed because email is empty");
				return false;
			}
			
			$email = trim($email);
			
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::teamInvitationIDForEmail() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$email = mysql_real_escape_string($email, $link);
			$sql = "SELECT invitationid FROM tdo_team_invitations WHERE teamid='$teamID' AND email='$email' AND membership_type=$membershipType";
			$result = mysql_query($sql, $link);
			if ($result)
			{
				$row = mysql_fetch_array($result);
				if (($row) && (isset($row['invitationid'])))
				{
					$invitationID = $row['invitationid'];
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return $invitationID;
				}
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		public static function removeTeamMember($teamID, $userID, $membershipType, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::removeTeamMember() failed because teamID is empty");
				return false;
			}
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::removeTeamMember() failed because userID is empty");
				return false;
			}
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::removeTeamMember() could not get DB connection.");
					return false;
				}
			}
			
			//
			// For normal members, we have to do TWO things, so do this in a
			// transaction:
			//	1. Remove the user from the tdo_team_members/tdo_team_admins table.
			//	2. Reset the user's expiration date to "now" (member only, not admins)
			//
			// Outside the transaction:
			//  3. Remove the member from any of the team-owned lists
			
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::removeTeamMember() couldn't start a transaction: " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			//
			// 1. Remove the user from the team.
			//
			if (!TDOTeamAccount::deleteUserFromTeam($userID, $teamID, $membershipType, $link))
			{
				error_log("TDOTeamAccount::removeTeamMember() couldn't delete a user ($userID) from the team ($teamID).");
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$newExpirationTimestamp = time(); // now, expire immediately
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				//
				// 2. Reset the normal user's expiration date to be now with no team ID on their account
				//
				$subscriptionID = TDOSubscription::getSubscriptionIDForUserID($userID);
				if (!$subscriptionID)
				{
					error_log("TDOTeamAccount::removeTeamMember() couldn't find a valid subscription for the specified user (teamID: $teamID, userID: $userID, membershipType: $membershipType).");
					mysql_query("ROLLBACK", $link);
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
				
				if (!TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, SUBSCRIPTION_TYPE_UNKNOWN, SUBSCRIPTION_LEVEL_TRIAL, NULL, $link))
				{
					error_log("TDOTeamAccount::removeTeamMember() couldn't update a subscription ($subscriptionID) for the user ($userID), in the team ($teamID).");
					mysql_query("ROLLBACK", $link);
					if($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::removeTeamMember() couldn't commit transaction (teamID: $teamID, userID: $userID, membershipType: $membershipType)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Remove the member from any of the team-owned lists
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				$sharedLists = TDOList::getSharedListsForTeam($teamID, true, $link);
				if (!empty($sharedLists))
				{
					foreach ($sharedLists as $teamList)
					{
						$listID = $teamList->listId();
						TDOList::removeUserFromList($listID, $userID, $link);
					}
				}
			}
			
			if (!TDOTeamAccount::notifyTeamAdminsOfRemovedMember($teamID, $userID, $membershipType, $link))
			{
				// Non-fatal error
				error_log("TDOTeamAccount::removeTeamMember() couldn't email the team ($teamID) administrators that a team member ($userID) was just removed.");
			}
			
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				$teamName = TDOTeamAccount::teamNameForTeamID($teamID, $link);
				$displayName = TDOUser::displayNameForUserId($userID);
				$email = TDOUser::usernameForUserId($userID);
				
				TDOMailer::notifyTeamMemberOfRemoval($teamName, $displayName, $email, $newExpirationTimestamp);
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $teamID;
		}
		
		public static function teamIDForInvitationID($invitationID, $link=NULL)
		{
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::teamIDForInvitationID() failed because invitationID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::teamIDForInvitationID() could not get DB connection.");
					return false;
				}
			}
			
			$invitationID = mysql_real_escape_string($invitationID, $link);
			$sql = "SELECT teamid FROM tdo_team_invitations WHERE invitationid='$invitationID'";
			if ($result = mysql_query($sql, $link))
			{
				if ($row = mysql_fetch_array($result))
				{
					$teamID = $row['teamid'];
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					
					return $teamID;
				}
			}
			else
			{
				error_log("TDOTeamAccount::teamIDForInvitationID($invitationID) encountered an error reading from the database: " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		public static function emailForTeamInvitationID($invitationID, $link=NULL)
		{
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::emailForTeamInvitationID() failed because invitationID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::emailForTeamInvitationID() could not get DB connection.");
					return false;
				}
			}
			
			$invitationID = mysql_real_escape_string($invitationID, $link);
			$sql = "SELECT email FROM tdo_team_invitations WHERE invitationid='$invitationID'";
			if ($result = mysql_query($sql, $link))
			{
				if ($row = mysql_fetch_array($result))
				{
					$email = $row['email'];
					$email = trim($email);
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					
					return $email;
				}
			}
			else
			{
				error_log("TDOTeamAccount::emailForTeamInvitationID($invitationID) encountered an error reading from the database: " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		public static function sendTeamInvitation($adminUserID, $invitationID, $email, $membershipType)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::sendTeamInvitation() failed because adminUserID is empty");
				return false;
			}
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::sendTeamInvitation() failed because invitationID is empty");
				return false;
			}
			if (!isset($email))
			{
				error_log("TDOTeamAccount::sendTeamInvitation() failed because email is empty");
				return false;
			}
			
			$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID);
			if (!$teamID)
			{
				error_log("TDOTeamAccount::sendTeamInvitation() failed because it could not determine the teamID from the invitationID ($invitationID)");
				return false;
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID))
			{
				error_log("TDOTeamAccount::sendTeamInvitation() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				return false;
			}
			
			$fromUserName = trim(TDOUser::displayNameForUserId($adminUserID));
			
			// Ensure the email is valid
			$email = TDOMailer::validate_email($email);
			if ($email && strlen($email) <= USER_NAME_LENGTH)
			{
				$invitationURL = SITE_PROTOCOL . SITE_BASE_URL . "?acceptTeamInvitation=true&invitationid=" . $invitationID;
				
				$teamName = TDOTeamAccount::teamNameForTeamID($teamID);
				
				if (TDOMailer::sendTeamInvitation($fromUserName, $email, $invitationURL, $teamName, $membershipType))
				{
					return true;
				}
				else
				{
					error_log("TDOTeamAccount::sendTeamInvitation(adminUserID: $adminUserID) encountered an error sending an invitation email to $email");
				}
			}
			else
			{
				error_log("TDOTeamAccount::sendTeamInvitation(adminUserID: $adminUserID) called with an invalid email address: $email");
			}
			
			
			
			return false;
		}
		
		
		public static function createTeamInvitation($adminUserID, $teamID, $email, $membershipType, $link=NULL)
		{
			if (!isset($adminUserID))
			{
				error_log("TDOTeamAccount::createTeamInvitation() failed because adminUserID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::createTeamInvitation() failed because teamID is empty");
				return false;
			}
			if (!isset($email))
			{
				error_log("TDOTeamAccount::createTeamInvitation() failed because email is empty");
				return false;
			}
			
			$email = trim($email);
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::createTeamInvitation() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the adminUserID is indeed a team administrator.
			// If they are not, do not allow this to happen.
			if (!TDOTeamAccount::isAdminForTeam($adminUserID, $teamID, $link))
			{
				error_log("TDOTeamAccount::createTeamInvitation() called with a userID ($adminUserID) that is NOT an administrator of the team ($teamID)");
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$invitedUserID = TDOUser::userIdForUserName($email);
			
			$invitationID = TDOUtil::uuid();
			$adminUserID = mysql_real_escape_string($adminUserID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			$email = strtolower($email);
			$email = mysql_real_escape_string($email, $link);
			
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$now = time();
			$sql = "INSERT INTO tdo_team_invitations (invitationid, userid, teamid, email, invited_userid, timestamp, membership_type) VALUES (";
			$sql .= "'$invitationID', '$adminUserID', '$teamID', '$email', ";
			if ($invitedUserID)
				$sql .= "'$invitedUserID', ";
			else
				$sql .= "NULL, ";
			$sql .= "$now, $membershipType)";
			$response = mysql_query($sql, $link);
			if ($response)
			{
				// Log the invitation
				TDOChangeLog::addChangeLog($teamID, $adminUserID, $invitationID, $email, ITEM_TYPE_TEAM_INVITATION, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				$invitationInfo = array(
										"invitationid" => $invitationID,
										"teamid" => $teamID,
										"email" => $email
										);
				return $invitationInfo;
			}
			else
			{
				error_log("TDOTeamAccount::createTeamInvitation() failed (teamID: $teamID, adminUserID: $adminUserID, email: $email): " . mysql_error());
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return false;
		}
		
		
		public static function deleteTeamInvitation($invitationID, $link=NULL)
		{
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::deleteTeamInvitation() failed because invitationID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::deleteTeamInvitation() could not get DB connection.");
					return false;
				}
			}
			
			$invitationID = mysql_real_escape_string($invitationID, $link);
			$sql = "DELETE FROM tdo_team_invitations WHERE invitationid='$invitationID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::deleteTeamInvitation() unable to delete invitation ($invitationID): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		public static function isValidTeamInvitation($invitationID, $link=NULL)
		{
			// Check to see whether the invitation exists and if the teamID
			// specified in the invitation record still exists. Return false if
			// either of these are not true.
			
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::isValidTeamInvitation() failed because invitationID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::isValidTeamInvitation() could not get DB connection.");
					return false;
				}
			}
			
			// Check to see if the teamID specified in the invitation still exists.
			$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID, $link);
			
			if (!$teamID)
			{
				error_log("TDOTeamAccount::isValidTeamInvitation() could not get a teamID for the invitationID: $invitationID");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Try to read the team name from the team. If that fails, the team
			// no longer exists.
			$teamName = TDOTeamAccount::teamNameForTeamID($teamID, $link);
			if (!$teamName)
			{
				error_log("TDOTeamAccount::isValidTeamInvitation() could not find an existing team (teamID: $teamID) from the invitationID ($invitationID). The team must no longer exist.");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// If the code reaches this point, the invitation exists and the
			// corresponding team still exists, so the invitation is good!
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		// If successful, return the team account object.
		//
		// As of Todo Cloud Web 2.4 we allow IAP users to join a team. The
		// service should not change an IAP user account's expiration date, but
		// *SHOULD* consume a license slot.
		//
		// If a user has extra time on their personal subscription (and they are
		// *NOT* an IAP user), the default behavior will be to send a promo code
		// to them for their remaining time. To change this behavior, set
		// donateSubscriptionToTeam=true.
		public static function acceptTeamInvitation($invitationID, $userID, $donateSubscriptionToTeam=false, $link=NULL)
		{
			if (!isset($invitationID))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() failed because invitationID is empty");
				return false;
			}
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() failed because userID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::acceptTeamInvitation() could not get DB connection.");
					return false;
				}
			}
			
			// Get info about the invitation so we know if this is an admin or
			// a member joining.
			$invitationInfo = TDOTeamAccount::invitationInfoForInvitationID($invitationID, $link);
			if (!$invitationInfo)
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() called with an invalid invitationID: $invitationID");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			$membershipType = $invitationInfo['membershipType'];
			
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				// System integrity check: Make sure that this user isn't already a
				// member of the team.
				$existingTeam = TDOTeamAccount::getTeamForTeamMember($userID, $link);
				if ($existingTeam)
				{
					error_log("TDOTeamAccount::acceptTeamInvitation() determined that the user ($userID) already belongs to a team (" . $existingTeam->getTeamID() . ") and cannot join another team.");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
						
			// Get info about the team
			$teamID = TDOTeamAccount::teamIDForInvitationID($invitationID, $link);
			if (!$teamID)
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() could not get a teamID for the invitationID: $invitationID");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
			if (!$teamAccount)
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() could not get read the team for the teamID ($teamID) from the invitationID: $invitationID");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				// System integrity check: Make sure that the number of team members
				// will not exceed the number of members specified in the
				// newLicenseCount property of the team.
				$currentTeamMemberCount = TDOTeamAccount::getCurrentTeamMemberCount($teamID, TEAM_MEMBERSHIP_TYPE_MEMBER, $link);
				$newTeamMemberCount = $currentTeamMemberCount + 1;
				if ($newTeamMemberCount > $teamAccount->getNewLicenseCount())
				{
					error_log("TDOTeamAccount::acceptTeamInvitation() determined that if it were to allow a new user ($userID) into the team membership, it would exceed the number of team members currently allowed in the team.");
					if ($closeLink)
						TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			//
			// Do all of the following in a transaction
			// 1. Add the user to the team membership/admin table
			// 2. NON-IAP Users only: Extend the user's subscription
			//    (for type "member" only)
			// 3. Delete the invitation
			// 4. For IAP Users: Email instructional email about how to cancel
			//    their auto-renewing IAP
			// 5. For NON-IAP Users: Determine the appropriate action if their
			//    personal account still has remaining subscription time
			
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() couldn't start a transaction: " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			//
			// 1. Add the user to the team membership table
			//
			if (!TDOTeamAccount::addUserToTeam($userID, $teamID, $membershipType, $donateSubscriptionToTeam, $link))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() couldn't add a user ($userID) to the team ($teamID), from the invitationID ($invitationID).");
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			//
			// 3. Delete the invitation
			//
			if (!TDOTeamAccount::deleteTeamInvitation($invitationID, $link))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() everything else worked, but couldn't delete the original invitation ($invitationID).");
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// SUCCESS!
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::acceptTeamInvitation() couldn't commit transaction (invitationID: $invitationID, userID: $userID)" . mysql_error());
				mysql_query("ROLLBACK", $link);
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			// Also add the user to the team's main shared list
			if ($membershipType == TEAM_MEMBERSHIP_TYPE_MEMBER)
			{
				$listID = $teamAccount->getMainListID();
				
				if (!TDOList::shareWithUser($listID, $userID, LIST_MEMBERSHIP_MEMBER))
				{
					error_log("TDOTeamAccount::acceptTeamInvitation() unable to add the user to the team's main shared list.");
				}
			}
			
			if (!TDOTeamAccount::notifyTeamAdminsOfNewMember($teamAccount->getTeamName(), $userID, $teamID, $membershipType, $link))
			{
				// Non-fatal error
				error_log("TDOTeamAccount::acceptTeamInvitation() couldn't email the team ($teamID) administrators that a new team member ($userID) joined.");
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $teamAccount;
		}
		
		public static function saveSubscriptionMonthsToTeamCredit($teamID, $userID, $numberOfSubscriptionMonths, $markConsumed=false, $link=NULL)
		{
			// Insert a new record into the tdo_team_subscription_credits table
			// to keep track of personal subscriptions that are donated to a
			// team account when a new team member joins.
			$donation_date = time();
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::saveSubscriptionMonthsToTeamCredit() could not get DB connection.");
					return false;
				}
			}
			
			$sql = "INSERT INTO tdo_team_subscription_credits (teamid,userid,donation_date,donation_months_count) VALUES ('$teamID', '$userID', $donation_date, $numberOfSubscriptionMonths)";
			if ($markConsumed)
			{
				$sql = "INSERT INTO tdo_team_subscription_credits (teamid,userid,donation_date,donation_months_count,consumed_date) VALUES ('$teamID', '$userID', $donation_date, $numberOfSubscriptionMonths, $donation_date)";
			}
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::saveSubscriptionMonthsToTeamCredit() unable to save personal subscription months to team credits (User ID: " . $userID . ", Team ID: " . $teamID . "): " . mysql_error());
				if($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			if($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		// Returns ALL the credits donated by a specific user to a team.
		public static function teamCreditsDonatedByMemberToTeam($userID, $teamID, $donationDate, $link=NULL)
		{
			$teamCredits = array();
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::teamCreditsDonatedByMemberToTeam() unable to get a link to the DB.");
					return false;
				}
			}
			
			$userID = mysql_real_escape_string($userID, $link);
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT userid,donation_date,donation_months_count FROM tdo_team_subscription_credits WHERE teamid='$teamID' AND userid='$userID' AND consumed_date IS NULL AND refunded_date IS NULL ORDER BY donation_date";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::teamCreditsDonatedByMemberToTeam() failed to make the SQL call" . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$userID = $row['userid'];
				$donationDate = $row['donation_date'];
				$donationMonths = $row['donation_months_count'];
				
				$credit = array(
								"userid" => $userID,
								"donationDate" => $donationDate,
								"numOfMonths" => $donationMonths
								);
				
//				if (!empty($row['consumed_date']))
//					$credit['consumedDate'] = $row['consumed_date'];
//				
//				if (!empty($row['refunded_date']))
//					$credit['refundedDate'] = $row['refunded_date'];
				
				$teamCredits[] = $credit;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $teamCredits;
		}
		
		public static function activeCreditsForTeam($teamID, $link=NULL)
		{
			if (empty($teamID))
			{
				error_log("TDOTeamAccount::activeCreditsForTeam() sent empty teamID.");
				return false;
			}
			
			$teamCredits = array();
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::activeCreditsForTeam() unable to get a link to the DB.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT userid,donation_date,donation_months_count FROM tdo_team_subscription_credits WHERE teamid='$teamID' AND consumed_date IS NULL AND refunded_date IS NULL ORDER BY donation_date";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::activeCreditsForTeam() failed to make the SQL call" . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$userID = $row['userid'];
				$donationDate = $row['donation_date'];
				$donationMonths = $row['donation_months_count'];
				
				$credit = array(
								"userid" => $userID,
								"donationDate" => $donationDate,
								"numOfMonths" => $donationMonths
								);
				
				$teamCredits[] = $credit;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $teamCredits;
		}
		
		
		public static function numOfActiveTeamCreditMonthsForTeam($teamID, $link=NULL)
		{
			if (empty($teamID))
			{
				error_log("TDOTeamAccount::activeCreditsForTeam() sent empty teamID.");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::numOfActiveTeamCreditMonthsForTeam() unable to get a link to the DB.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT SUM(donation_months_count) FROM tdo_team_subscription_credits WHERE teamid='$teamID' AND consumed_date IS NULL AND refunded_date IS NULL";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::numOfActiveTeamCreditMonthsForTeam() failed to make the SQL call" . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return false;
			}
			
			$numOfCredits = 0;
			if ($row = mysql_fetch_array($result))
			{
				$numOfCredits = $row[0];
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $numOfCredits;
		}
		
		
		public static function refundTeamCreditsForTeamIfNeeded($teamID)
		{
			$activeTeamCredits = TDOTeamAccount::activeCreditsForTeam($teamID);
			if (empty($activeTeamCredits))
			{
				return false;
			}
			
			foreach ($activeTeamCredits as $credit)
			{
				// Steps for refund:
				//	1. Create new promo code
				//	2. Mark the credit as consumed
				//	3. Mail the promo code
				$userID = $credit['userid'];
				$numOfMonths = $credit['numOfMonths'];
				$donationDate = $credit['donationDate'];
				
				// 1. Create new promo code
                $promoCodeNote = sprintf(_('Refund donated team credit due to team cancellation of %s months for userID: %s'), $numOfMonths, $userID);
				$promoCodeInfo = TDOPromoCode::createPromoCode($numOfMonths, $userID, $userID, $promoCodeNote);
				
				if (empty($promoCodeInfo) || isset($promoCodeInfo['errcode']))
				{
					error_log("TDOTeamAccount::refundTeamCreditsForTeamIfNeeded() could not create a promo code for $numOfMonths for userID: $userID");
					continue;
				}
				
				$promoCode = $promoCodeInfo['promocode'];
				$promoLink = $promoCodeInfo['promolink'];
				
				// 2. Mark the credit as consumed
				if (!TDOTeamAccount::markTeamCreditAsConsumed($teamID, $userID, $donationDate))
				{
					// Delete the new promo code we just created
					TDOPromoCode::deletePromoCode($promoCode);
					
					error_log('TDOTeamAccount::refundTeamCreditsForTeamIfNeeded() had an error marking a team ($teamID) credit as consumed for user ($userID)');
					continue;
				}
				
				// 3. Mail the promo code
				if(!TDOMailer::sendPromoCodeForTeamCreditRefund($userID, $teamID, $numOfMonths, $promoCode, $promoLink))
				{
					// Delete the new promo code we just created
					TDOPromoCode::deletePromoCode($promoCode);
					
					// Reset the team credit
					TDOTeamAccount::updateTeamCreditWithNumberOfMonths($teamID, $userID, $donationDate, $numOfMonths);
					
					error_log("TDOTeamAccount::refundTeamCreditsForTeamIfNeeded() failed to send promo code to user ($userID) via email for team ($teamID).");
					continue;
				}
			}
			
			return true;
		}

		
		public static function notifyTeamAdminsOfNewMember($teamName, $userID, $teamID, $membershipType, $link=NULL)
		{
			if (!isset($teamName))
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfNewMember() failed because teamName is empty");
				return false;
			}
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfNewMember() failed because userID is empty");
				return false;
			}
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfNewMember() failed because teamID is empty");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::notifyTeamAdminsOfNewMember() could not get DB connection.");
					return false;
				}
			}
			
			$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamID, $link);
			if (!$teamAdminIDs)
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfNewMember() unable to determine any team administrators.");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$memberEmail = TDOUser::usernameForUserId($userID);
			$memberDisplayName = TDOUser::displayNameForUserId($userID);
			
			foreach($teamAdminIDs as $adminUserID)
			{
				// If the member is the admin, skip sending them an email
				if ($userID == $adminUserID)
					continue;
				
				$adminEmail = TDOUser::usernameForUserId($adminUserID);
				$adminDisplayName = TDOUser::displayNameForUserId($adminUserID);
				
				TDOMailer::sendTeamAdminNewMemberNotification($teamID, $teamName, $adminEmail, $adminDisplayName, $memberEmail, $memberDisplayName, $membershipType);
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		public static function notifyTeamAdminsOfRemovedMember($teamID, $userID, $membershipType, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfRemovedMember() failed because teamID is empty");
				return false;
			}
			if (!isset($userID))
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfRemovedMember() failed because userID is empty");
				return false;
			}
			if (($membershipType != TEAM_MEMBERSHIP_TYPE_MEMBER) && ($membershipType != TEAM_MEMBERSHIP_TYPE_ADMIN))
				$membershipType = TEAM_MEMBERSHIP_TYPE_MEMBER;
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::notifyTeamAdminsOfRemovedMember() could not get DB connection.");
					return false;
				}
			}
			
			$teamAdminIDs = TDOTeamAccount::getAdminUserIDsForTeam($teamID, $link);
			if (!$teamAdminIDs)
			{
				error_log("TDOTeamAccount::notifyTeamAdminsOfRemovedMember() unable to determine any team administrators.");
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				return false;
			}
			
			$teamName = TDOTeamAccount::teamNameForTeamID($teamID, $link);
			$memberEmail = TDOUser::usernameForUserId($userID);
			$memberDisplayName = TDOUser::displayNameForUserId($userID);
			
			foreach($teamAdminIDs as $adminUserID)
			{
				// If the member is the admin, skip sending them an email
				if ($userID == $adminUserID)
					continue;
				
				$adminEmail = TDOUser::usernameForUserId($adminUserID);
				$adminDisplayName = TDOUser::displayNameForUserId($adminUserID);
				
				TDOMailer::sendTeamAdminMemberRemovedNotification($teamID, $teamName, $adminEmail, $adminDisplayName, $memberEmail, $memberDisplayName, $membershipType);
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			return true;
		}
		
		public static function countryNameForCode($countryCode)
		{
			if (empty($countryCode))
			{
				return "";
			}
			
			switch ($countryCode)
			{
				case "US": return "United States";
				case "GB": return "United Kingdom";
				case "AF": return "Afghanistan";
				case "AL": return "Albania";
				case "DZ": return "Algeria";
				case "AS": return "American Samoa";
				case "AD": return "Andorra";
				case "AO": return "Angola";
				case "AI": return "Anguilla";
				case "AQ": return "Antarctica";
				case "AG": return "Antigua and Barbuda";
				case "AR": return "Argentina";
				case "AM": return "Armenia";
				case "AW": return "Aruba";
				case "AU": return "Australia";
				case "AT": return "Austria";
				case "AZ": return "Azerbaijan";
				case "BS": return "Bahamas";
				case "BH": return "Bahrain";
				case "BD": return "Bangladesh";
				case "BB": return "Barbados";
				case "BY": return "Belarus";
				case "BE": return "Belgium";
				case "BZ": return "Belize";
				case "BJ": return "Benin";
				case "BM": return "Bermuda";
				case "BT": return "Bhutan";
				case "BO": return "Bolivia";
				case "BA": return "Bosnia and Herzegovina";
				case "BW": return "Botswana";
				case "BV": return "Bouvet Island";
				case "BR": return "Brazil";
				case "IO": return "British Indian Ocean Territory";
				case "BN": return "Brunei Darussalam";
				case "BG": return "Bulgaria";
				case "BF": return "Burkina Faso";
				case "BI": return "Burundi";
				case "KH": return "Cambodia";
				case "CM": return "Cameroon";
				case "CA": return "Canada";
				case "CV": return "Cape Verde";
				case "KY": return "Cayman Islands";
				case "CF": return "Central African Republic";
				case "TD": return "Chad";
				case "CL": return "Chile";
				case "CN": return "China";
				case "CX": return "Christmas Island";
				case "CC": return "Cocos (Keeling) Islands";
				case "CO": return "Colombia";
				case "KM": return "Comoros";
				case "CG": return "Congo";
				case "CD": return "Congo, the Democratic Republic of the";
				case "CK": return "Cook Islands";
				case "CR": return "Costa Rica";
				case "CI": return "Cote d'Ivoire";
				case "HR": return "Croatia (Hrvatska)";
				case "CU": return "Cuba";
				case "CY": return "Cyprus";
				case "CZ": return "Czech Republic";
				case "DK": return "Denmark";
				case "DJ": return "Djibouti";
				case "DM": return "Dominica";
				case "DO": return "Dominican Republic";
				case "TP": return "East Timor";
				case "EC": return "Ecuador";
				case "EG": return "Egypt";
				case "SV": return "El Salvador";
				case "GQ": return "Equatorial Guinea";
				case "ER": return "Eritrea";
				case "EE": return "Estonia";
				case "ET": return "Ethiopia";
				case "FK": return "Falkland Islands (Malvinas)";
				case "FO": return "Faroe Islands";
				case "FJ": return "Fiji";
				case "FI": return "Finland";
				case "FR": return "France";
				case "FX": return "France, Metropolitan";
				case "GF": return "French Guiana";
				case "PF": return "French Polynesia";
				case "TF": return "French Southern Territories";
				case "GA": return "Gabon";
				case "GM": return "Gambia";
				case "GE": return "Georgia";
				case "DE": return "Germany";
				case "GH": return "Ghana";
				case "GI": return "Gibraltar";
				case "GR": return "Greece";
				case "GL": return "Greenland";
				case "GD": return "Grenada";
				case "GP": return "Guadeloupe";
				case "GU": return "Guam";
				case "GT": return "Guatemala";
				case "GN": return "Guinea";
				case "GW": return "Guinea-Bissau";
				case "GY": return "Guyana";
				case "HT": return "Haiti";
				case "HM": return "Heard and Mc Donald Islands";
				case "VA": return "Holy See (Vatican City State)";
				case "HN": return "Honduras";
				case "HK": return "Hong Kong";
				case "HU": return "Hungary";
				case "IS": return "Iceland";
				case "IN": return "India";
				case "ID": return "Indonesia";
				case "IR": return "Iran (Islamic Republic of)";
				case "IQ": return "Iraq";
				case "IE": return "Ireland";
				case "IL": return "Israel";
				case "IT": return "Italy";
				case "JM": return "Jamaica";
				case "JP": return "Japan";
				case "JO": return "Jordan";
				case "KZ": return "Kazakhstan";
				case "KE": return "Kenya";
				case "KI": return "Kiribati";
				case "KP": return "Korea, Democratic People's Republic of";
				case "KR": return "Korea, Republic of";
				case "KW": return "Kuwait";
				case "KG": return "Kyrgyzstan";
				case "LA": return "Lao People's Democratic Republic";
				case "LV": return "Latvia";
				case "LB": return "Lebanon";
				case "LS": return "Lesotho";
				case "LR": return "Liberia";
				case "LY": return "Libyan Arab Jamahiriya";
				case "LI": return "Liechtenstein";
				case "LT": return "Lithuania";
				case "LU": return "Luxembourg";
				case "MO": return "Macau";
				case "MK": return "Macedonia, The Former Yugoslav Republic of";
				case "MG": return "Madagascar";
				case "MW": return "Malawi";
				case "MY": return "Malaysia";
				case "MV": return "Maldives";
				case "ML": return "Mali";
				case "MT": return "Malta";
				case "MH": return "Marshall Islands";
				case "MQ": return "Martinique";
				case "MR": return "Mauritania";
				case "MU": return "Mauritius";
				case "YT": return "Mayotte";
				case "MX": return "Mexico";
				case "FM": return "Micronesia, Federated States of";
				case "MD": return "Moldova, Republic of";
				case "MC": return "Monaco";
				case "MN": return "Mongolia";
				case "MS": return "Montserrat";
				case "MA": return "Morocco";
				case "MZ": return "Mozambique";
				case "MM": return "Myanmar";
				case "NA": return "Namibia";
				case "NR": return "Nauru";
				case "NP": return "Nepal";
				case "NL": return "Netherlands";
				case "AN": return "Netherlands Antilles";
				case "NC": return "New Caledonia";
				case "NZ": return "New Zealand";
				case "NI": return "Nicaragua";
				case "NE": return "Niger";
				case "NG": return "Nigeria";
				case "NU": return "Niue";
				case "NF": return "Norfolk Island";
				case "MP": return "Northern Mariana Islands";
				case "NO": return "Norway";
				case "OM": return "Oman";
				case "PK": return "Pakistan";
				case "PW": return "Palau";
				case "PA": return "Panama";
				case "PG": return "Papua New Guinea";
				case "PY": return "Paraguay";
				case "PE": return "Peru";
				case "PH": return "Philippines";
				case "PN": return "Pitcairn";
				case "PL": return "Poland";
				case "PT": return "Portugal";
				case "PR": return "Puerto Rico";
				case "QA": return "Qatar";
				case "RE": return "Reunion";
				case "RO": return "Romania";
				case "RU": return "Russian Federation";
				case "RW": return "Rwanda";
				case "KN": return "Saint Kitts and Nevis";
				case "LC": return "Saint LUCIA";
				case "VC": return "Saint Vincent and the Grenadines";
				case "WS": return "Samoa";
				case "SM": return "San Marino";
				case "ST": return "Sao Tome and Principe";
				case "SA": return "Saudi Arabia";
				case "SN": return "Senegal";
				case "SC": return "Seychelles";
				case "SL": return "Sierra Leone";
				case "SG": return "Singapore";
				case "SK": return "Slovakia (Slovak Republic)";
				case "SI": return "Slovenia";
				case "SB": return "Solomon Islands";
				case "SO": return "Somalia";
				case "ZA": return "South Africa";
				case "GS": return "South Georgia and the South Sandwich Islands";
				case "ES": return "Spain";
				case "LK": return "Sri Lanka";
				case "SH": return "St. Helena";
				case "PM": return "St. Pierre and Miquelon";
				case "SD": return "Sudan";
				case "SR": return "Suriname";
				case "SJ": return "Svalbard and Jan Mayen Islands";
				case "SZ": return "Swaziland";
				case "SE": return "Sweden";
				case "CH": return "Switzerland";
				case "SY": return "Syrian Arab Republic";
				case "TW": return "Taiwan, Province of China";
				case "TJ": return "Tajikistan";
				case "TZ": return "Tanzania, United Republic of";
				case "TH": return "Thailand";
				case "TG": return "Togo";
				case "TK": return "Tokelau";
				case "TO": return "Tonga";
				case "TT": return "Trinidad and Tobago";
				case "TN": return "Tunisia";
				case "TR": return "Turkey";
				case "TM": return "Turkmenistan";
				case "TC": return "Turks and Caicos Islands";
				case "TV": return "Tuvalu";
				case "UG": return "Uganda";
				case "UA": return "Ukraine";
				case "AE": return "United Arab Emirates";
				case "GB": return "United Kingdom";
				case "US": return "United States";
				case "UM": return "United States Minor Outlying Islands";
				case "UY": return "Uruguay";
				case "UZ": return "Uzbekistan";
				case "VU": return "Vanuatu";
				case "VE": return "Venezuela";
				case "VN": return "Viet Nam";
				case "VG": return "Virgin Islands (British)";
				case "VI": return "Virgin Islands (U.S.)";
				case "WF": return "Wallis and Futuna Islands";
				case "EH": return "Western Sahara";
				case "YE": return "Yemen";
				case "YU": return "Yugoslavia";
				case "ZM": return "Zambia";
				case "ZW": return "Zimbabwe";
			}
			
			return "";
		}
		
		
		public static function getTeamPurchaseHistory($teamID, $link=NULL)
		{
			if (!isset($teamID))
			{
				error_log("TDOTeamAccount::getTeamPurchaseHistory() called with an empty teamID");
				return false;
			}
			
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getTeamPurchaseHistory() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			$purchases = array();
			$sql = "SELECT timestamp,type,amount,charge_description,license_count FROM tdo_stripe_payment_history WHERE teamid='$teamID' ORDER BY timestamp DESC";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOSubscription::getTeamPurchaseHistory() failed to make the SQL call" . mysql_error());
				if ($closeLink)
					TDOUtil::closeDBLink($link);
				
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$timestamp = $row['timestamp'];
				$subscriptionType = $row['type'];
                $licenseCount = $row['license_count'];
				$amount = $row['amount'] / 100;
				$chargeDescription = $row['charge_description'];
				
				$subscriptionTypeString = "month";
				if ($subscriptionType == SUBSCRIPTION_TYPE_UNKNOWN)
					continue;
				else if ($subscriptionType == SUBSCRIPTION_TYPE_YEAR)
					$subscriptionTypeString = "year";
				
				$purchase = array(
								  "timestamp" => $timestamp,
								  "subscriptionType" => $subscriptionTypeString,
								  "description" => $chargeDescription,
								  "amount" => $amount,
								  "licenseCount" => $licenseCount
								  );
				
				$purchases[] = $purchase;
			}
			
			if ($closeLink)
				TDOUtil::closeDBLink($link);
			
			return $purchases;
		}
		
		//
		// Team autorenewal functions
		//
		
		public static function getAutorenewableTeamSubscriptionsWithinDate($expirationDate)
		{
			if (empty($expirationDate))
				return false;
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTeamAccount::getAutorenewableTeamSubscriptionsWithinDate() failed to get dblink");
				return false;
			}
            
            $expirationDate = intval($expirationDate);
			
			$teamIDs = array();
			$sql = "SELECT teamid FROM tdo_team_accounts WHERE teamid NOT IN (SELECT tdo_team_autorenew_history.teamid FROM tdo_team_autorenew_history) AND expiration_date <= $expirationDate";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOTeamAccount::getAutorenewableTeamSubscriptionsWithinDate() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$teamID = $row['teamid'];
                if(in_array($teamID, $teamIDs) == false)
                    $teamIDs[] = $teamID;
			}
            
			TDOUtil::closeDBLink($link);
			
			return $teamIDs;
		}
		
		
		public static function addTeamsForAutorenewal($teamIDs)
		{
			if(empty($teamIDs))
			{
				error_log("TDOTeamAccount::addTeamsForAutorenewal() failed because teamIDs is empty");
				return false;
			}
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTeamAccount::addTeamsForAutorenewal() failed to get dblink");
				return false;
			}
			
			if (!mysql_query("START TRANSACTION", $link))
			{
				error_log("TDOTeamAccount::addTeamsForAutorenewal() couldn't start transaction: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
            foreach($teamIDs as $teamID)
			{
				$teamID = mysql_real_escape_string($teamID, $link);
				
				$sql = "INSERT INTO tdo_team_autorenew_history (teamid) VALUES ('$teamID')";
				if (!mysql_query($sql, $link))
				{
					error_log("TDOTeamAccount::addTeamsForAutorenewal() unable to add teams for autorenewal: " . mysql_error());
					mysql_query("ROLLBACK", $link);
					TDOUtil::closeDBLink($link);
					return false;
				}
			}
			
			if(!mysql_query("COMMIT", $link))
			{
				error_log("TDOTeamAccount::addTeamsForAutorenewal() couldn't commit transaction after adding teams:" . mysql_error());
				mysql_query("ROLLBACK", $link);
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		public static function getFailedTeamAutorenewableSubscriptions()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTeamAccount::getFailedTeamAutorenewableSubscriptions() failed to get dblink");
				return false;
			}
			
			$teamIDs = array();
			$sql = "SELECT teamid FROM tdo_team_autorenew_history WHERE renewal_attempts > 0 AND renewal_attempts < " . TEAM_SUBSCRIPTION_RETRY_MAX_ATTEMPTS;
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOTeamAccount::getFailedTeamAutorenewableSubscriptions() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$teamID = $row['teamid'];
				$teamIDs[] = $teamID;
			}
			
			TDOUtil::closeDBLink($link);
			
			return $teamIDs;
		}
		
		
		public static function getNewTeamAutorenewableSubscriptions()
		{
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTeamAccount::getNewTeamAutorenewableSubscriptions() failed to get dblink");
				return false;
			}
			
			$teamIDs = array();
			$sql = "SELECT teamid FROM tdo_team_autorenew_history WHERE renewal_attempts = 0";
			$result = mysql_query($sql, $link);
			if (!$result)
			{
				error_log("TDOTeamAccount::getNewTeamAutorenewableSubscriptions() failed to make the SQL call" . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			while ($row = mysql_fetch_array($result))
			{
				$teamID = $row['teamid'];
				$teamIDs[] = $teamID;
			}
			
			TDOUtil::closeDBLink($link);
			
			return $teamIDs;
		}
		
		
		public static function updateAutorenewFailureCountsForTeamID($teamID, $failureReason = NULL)
		{
			if(empty($teamID))
			{
				error_log("TDOTeamAccount::updateAutorenewFailureCountsForTeamID() failed because subscriptionID is empty");
				return false;
			}
			
			$link = TDOUtil::getDBLink();
			if(!$link)
			{
				error_log("TDOTeamAccount::updateAutorenewFailureCountsForTeamID() failed to get dblink");
				return false;
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);

            //If the team has reached TEAM_SUBSCRIPTION_RETRY_MAX_ATTEMPTS, we need to email support so
            //their account can be taken care of. Otherwise, they will never be able to auto-renew
            //again, even if they switch payment systems.
			$renewalAttempts = TDOTeamAccount::getRenewalAttemptsForTeam($teamID, $link);
            $newRenewalAttempts = $renewalAttempts + 1;
            if($newRenewalAttempts >= TEAM_SUBSCRIPTION_RETRY_MAX_ATTEMPTS)
            {
                if(TDOMailer::sendTeamSubscriptionRenewalMaxRetryAttemptsReachedNotification($teamID) == false)
                {
                    error_log("TDOTeamAccount::updateAutorenewFailureCountsForTeamID failed to send email to support.");
                    TDOUtil::closeDBLink($link);
                    return false;
                }
            }
            
            
			$nowTimestamp = time();
			$sql = "UPDATE tdo_team_autorenew_history SET renewal_attempts = $newRenewalAttempts, attempted_time=$nowTimestamp";
			if (!empty($failureReason))
			{
				$failureReason = mysql_real_escape_string($failureReason, $link);
				$sql .= ", failure_reason='$failureReason'";
			}
			$sql .= " WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::updateAutorenewFailureCountsForTeamID() unable to update failed subscription autorenewal: " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
            
            
			TDOUtil::closeDBLink($link);
			return true;
		}
		
		
		public static function getRenewalAttemptsForTeam($teamID, $link = NULL)
		{
			$closeLink = false;
			if ($link == NULL)
			{
				$closeLink = true;
				$link = TDOUtil::getDBLink();
				if (!$link)
				{
					error_log("TDOTeamAccount::getRenewalAttemptsForTeam() could not get DB connection.");
					return false;
				}
			}
			
			$teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "SELECT renewal_attempts FROM tdo_team_autorenew_history WHERE teamid='$teamID'";
			$response = mysql_query($sql, $link);
			$renewalAttempts = 0;
			if($response)
			{
				if($row = mysql_fetch_array($response))
				{
					if(isset($row['renewal_attempts']))
					{
						$renewalAttempts = intval($row['renewal_attempts']);
					}
				}
			}
			
			if ($closeLink)
			{
				TDOUtil::closeDBLink($link);
			}
			
			return $renewalAttempts;
		}
		
		
		public static function removeTeamFromAutorenewQueue($teamID)
		{
            if(!isset($teamID))
                return false;
			
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOTeamAccount::removeTeamFromAutorenewQueue() unable to get link");
                return false;
            }
			
            $teamID = mysql_real_escape_string($teamID, $link);
			
			$sql = "DELETE FROM tdo_team_autorenew_history WHERE teamid='$teamID'";
			if (!mysql_query($sql, $link))
			{
				error_log("TDOTeamAccount::removeTeamFromAutorenewQueue() unable to delete teamID ($teamID): " . mysql_error());
				TDOUtil::closeDBLink($link);
				return false;
			}
			
			// SUCCESS!
            TDOUtil::closeDBLink($link);
            return true;
		}
		
		public static function processAutorenewalForTeam($teamID)
		{
			if (empty($teamID))
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() called with no teamID");
				return false;
			}
			
			$teamAccount = TDOTeamAccount::getTeamForTeamID($teamID);
			if (!$teamAccount)
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() unable to locate a team for ID: $teamID");
				TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Unable to locate a team for specified team ID");
				return false;
			}
			
			// Verify that all of the conditions are correct for autorenewing
			// the specified subscription with the Stripe information.  If all
			// the stars align, make the charge and update everything!
			
			$nowTimestamp = time();
			$advanceExpireDate = $nowTimestamp + SUBSCRIPTION_RENEW_LEAD_TIME;
			$expirationDate = $teamAccount->getExpirationDate();
			
			if ($expirationDate > $advanceExpireDate)
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() subscription must have already been updated.  Removing it from the autorenewal queue.");
				TDOTeamAccount::removeTeamFromAutorenewQueue($teamID);
				return true;
			}
			
			// We've determined that the subscription is valid for renewal, so
			// now determine whether this is a yearly or monthly renewal.
			$billingFrequency = $teamAccount->getBillingFrequency();
			if ($billingFrequency == SUBSCRIPTION_TYPE_UNKNOWN)
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() unable to determine what type of renewal (month/year) this is for team ID: $teamID");
				TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Unknown subscription type (month/year)");
				return false;
			}
            $billingUserID = $teamAccount->getBillingUserID();
            $user_locale = TDOUser::getLocaleForUser($billingUserID);
            setlocale(LC_ALL, $user_locale. '.' . DEFAULT_LOCALE_ENCODING);

            $billingFrequencyString = "monthly";
			$newLicenseCount = $teamAccount->getNewLicenseCount();
            $chargeDescription = sprintf(_('Todo Cloud Team Renewal - %s member(s), 1 mo'), $newLicenseCount);
			if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
			{
				$billingFrequencyString = "yearly";
                $chargeDescription = sprintf(_('Todo Cloud Team Renewal - %s member(s), 1 yr'), $newLicenseCount);
			}
			$zipCode = $teamAccount->getBizPostalCode();
			$pricingInfo = TDOTeamAccount::getTeamPricingInfo($billingFrequencyString, $newLicenseCount, true, $billingUserID, $teamID);
			if (!$pricingInfo)
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() unable to determine pricing information for team ID: $teamID");
				TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Unable to determine pricing information");
                setlocale(LC_ALL, DEFAULT_LOCALE_IN_USE. '.' . DEFAULT_LOCALE_ENCODING);
				return false;
			}
			
			// Now figure out what the new expiration date is. Always go with
			// the farthest date out. Either "NOW" or the expiration date so we
			// don't accidentally cheat the team out of any time.
			$now = time();
			$baseDatestamp = $now;
			if ($expirationDate > $now)
				$baseDatestamp = $expirationDate;
			
			$oneMonthPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_MONTHLY_DATE_INTERVAL);
			$extensionInterval = new DateInterval($oneMonthPeriodSetting);
			if ($billingFrequency == SUBSCRIPTION_TYPE_YEAR)
			{
				$oneYearPeriodSetting = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL', DEFAULT_SYSTEM_SETTING_SUBSCRIPTION_YEARLY_DATE_INTERVAL);
				$extensionInterval = new DateInterval($oneYearPeriodSetting);
			}
			
			$newExpirationDate = new DateTime('@' . $baseDatestamp, new DateTimeZone("UTC"));
			$newExpirationDate->add($extensionInterval);
			$newExpirationTimestamp = $newExpirationDate->getTimestamp();
			
			
			if (empty($billingUserID))
			{
				// The Todo Cloud system overloads the billing user so that if
				// it's not set, we consider the team account cancelled. Check
				// for outstanding team credits that were originally donated by
				// members joining the team with existing personal subscriptions.
				// Refund team credits as promo codes.
				TDOTeamAccount::refundTeamCreditsForTeamIfNeeded($teamID);
				
				// If auto-renewals failed this many times, we're going to
				// consider the team subscription expired. Time to notify the
				// entire team about it. Only send the subscription notice if
				// this is the first renewal attempt that failed (See:
				// https://github.com/Appigo/todo-issues/issues/1320).
				$renewalAttempts = TDOTeamAccount::getRenewalAttemptsForTeam($teamID);
				if ($renewalAttempts == 0)
				{
					TDOMailer::sendTeamExpiredNotification($teamAccount);
				}
				
				error_log("TDOTeamAccount::processAutorenewalForTeam() unable to determine an admin billing user for ID: $teamID");
				TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Unable to determine admin billing user for specified team ID");
                setlocale(LC_ALL, DEFAULT_LOCALE_IN_USE. '.' . DEFAULT_LOCALE_ENCODING);
				return false;
			}
			
			// Get the last4 credit card numbers from Stripe.
			$billingInfo = TDOSubscription::getSubscriptionBillingInfoForUser($billingUserID);
			if (!$billingInfo)
			{
				error_log("TDOTeamAccount::processAutorenewalForTeam() unable to get previous billing information for team ID: $teamID");
				TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Could not determine previous billing information (last4)");
                setlocale(LC_ALL, DEFAULT_LOCALE_IN_USE. '.' . DEFAULT_LOCALE_ENCODING);
				return false;
			}
			
			$last4 = $billingInfo['last4'];
			$unitPriceInCents = $pricingInfo['unitPrice'] * 100;
			$unitCombinedPriceInCents = $pricingInfo['unitCombinedPrice'] * 100;
			$subtotalInCents = $pricingInfo['subtotal'] * 100;
			$priceToChargeInCents = $pricingInfo['totalPrice'] * 100;
			$discountPercentage = $pricingInfo['discountPercentage'];
			$discountInCents = $pricingInfo['discountAmount'] * 100;
			$teamCreditMonths = $pricingInfo['teamCreditMonths'];
			$teamCreditsPriceDiscountInCents = $pricingInfo['creditsPriceDiscount'] * 100;
			$totalInCents = $pricingInfo['totalPrice'] * 100;
			
			// Only charge a user if the credits don't take care of it.
			if ($totalInCents > 0)
			{
				$stripeCharge = TDOSubscription::makeStripeCharge($billingUserID,
																  $teamID,
																  NULL, // NULL Stripe token because this should NOT be a brand new Stripe customer
																  $last4,
																  $unitPriceInCents,
																  $unitCombinedPriceInCents,
																  $subtotalInCents,
																  $discountPercentage,
																  $discountInCents,
																  $teamCreditMonths,
																  $teamCreditsPriceDiscountInCents,
																  $totalInCents,
																  $chargeDescription,
																  $billingFrequency,
																  $newExpirationTimestamp,
																  false, // $isGiftCodePurchase
																  $newLicenseCount);
				
				if (empty($stripeCharge) || isset($stripeCharge['errcode']))
				{
					error_log("TDOTeamAccount::processAutorenewalForTeam() failed when calling TDOSubscription::makeStripeCharge()");
					
					// TODO: Mail the user with the failure reason
					TDOTeamAccount::updateAutorenewFailureCountsForTeamID($teamID, "Error calling makeStripeCharge() with errCode = " . $stripeCharge['errcode'] . ", errDesc = " . $stripeCharge['errDesc']);
                    setlocale(LC_ALL, DEFAULT_LOCALE_IN_USE. '.' . DEFAULT_LOCALE_ENCODING);
					return false;
				}
			}
			else
			{
				// We need to email the billing user and also log some sort of
				// payment so it shows up in the team's purchase history.
				$username = TDOUser::usernameForUserId($billingUserID);
				$displayName = TDOUser::displayNameForUserId($billingUserID);
				$purchaseDate = time();
				$cardType = "-";
				$last4 = "xxxx";

				$unitPrice = $pricingInfo['unitPrice'];
				$unitCombinedPrice = $pricingInfo['unitCombinedPrice'];
				$discountAmount = $pricingInfo['discountAmount'];
				$teamCreditsDiscountAmount = $pricingInfo['creditsPriceDiscount'];
				$subtotalAmount = $pricingInfo['subtotal'];
				$purchaseAmount = $pricingInfo['totalPrice'];
				
				// Send a completely full purchase receipt for a team account
				TDOMailer::sendTeamPurchaseReceipt($username, $displayName, $teamID,
												   $purchaseDate, $cardType, $last4, $billingFrequency,
												   $unitPrice, $unitCombinedPrice,
												   $discountPercentage, $discountAmount,
												   $teamCreditMonths, $teamCreditsDiscountAmount,
												   $subtotalAmount, $purchaseAmount,
												   $newExpirationTimestamp,
												   $newLicenseCount);
				
				$stripeUserID = "-";
				$stripeChargeID = "-";
				
				TDOSubscription::logStripePayment($billingUserID, $teamID, $newLicenseCount, "-", "-", $cardType, $last4, $billingFrequency, $totalInCents, $purchaseDate, $chargeDescription);
			}
			
			if (!TDOTeamAccount::updateTeamAccountWithNewExpirationDate($teamID, $newExpirationTimestamp, $billingFrequency))
			{
				// Continue on, but this is a significant error!
				error_log("TDOTeamAccount::processAutorenewalForTeam() could not extend the expiration date (newExpirationDate - " . date("d M Y", $newExpirationTimestamp) . ") of a team ($teamID) that was just modified.");
				TDOMailer::sendTeamSubscriptionExpirationErrorNotification($teamID, $newExpirationTimestamp);
			}
			
			// If the new number of members doesn't match the original number of
			// members, we need to update the original license count to reflect
			// that the user just paid for an account.
			$origLicenseCount = $teamAccount->getLicenseCount();
			if ($origLicenseCount != $newLicenseCount)
			{
				if (!TDOTeamAccount::updateTeamAccountWithLicenseCounts($teamID, $newLicenseCount, $newLicenseCount))
				{
					// Continue on, but this is a significant error!
					error_log("TDOTeamAccount::processAutorenewalForTeam() could not update the license counts for an auto-renewed team ($teamID), licenses = $newLicenseCount");
					TDOMailer::sendTeamSubscriptionLicenseCountErrorNotification($teamID, $newLicenseCount);
				}
			}
			
			// Adjust the team donation credits
			if (!empty($pricingInfo['teamCredits']))
			{
				$teamCredits = $pricingInfo['teamCredits'];
				TDOTeamAccount::consumeTeamCreditsAfterPurchase($teamID, $teamCredits);
			}
			
			if ($totalInCents > 0)
			{
				$cardType = 'N/A';
				if (isset($stripeCharge->card))
				{
					$card = $stripeCharge->card;
					if (isset($card['type']))
						$cardType = $card['type'];
				}
				
				// Keep a record of the charge!
				$cardType = 'N/A';
				$last4 = 'XXXX';
				if (isset($stripeCharge->card))
				{
					$card = $stripeCharge->card;
					if (isset($card['type']))
						$cardType = $card['type'];
					if (isset($card['last4']))
						$last4 = $card['last4'];
				}
				
				$stripeCustomerID = $stripeCharge['customer'];
				
				TDOSubscription::logStripePayment($billingUserID, $teamID, $newLicenseCount, $stripeCustomerID, $stripeCharge->id, $cardType, $last4, $billingFrequency, $stripeCharge->amount, $now, $chargeDescription);
			}
			
			
			error_log("TDOTeamAccount::processAutorenewalForTeam() successfully processed an autorenewal for team ID: $teamID");
			
			TDOTeamAccount::removeTeamFromAutorenewQueue($teamID);
            setlocale(LC_ALL, DEFAULT_LOCALE_IN_USE. '.' . DEFAULT_LOCALE_ENCODING);
			return true;
		}

//        public static function getAllTaskCountByTeamId($team_id){
//
//            if(empty($teamIDs))
//            {
//                error_log("TDOTeamAccount::getAllTaskCountByTeamId() failed because teamID is empty");
//                return false;
//            }
//
//            $link = TDOUtil::getDBLink();
//            if(!$link)
//            {
//                error_log("TDOTeamAccount::getAllTaskCountByTeamId() failed to get dblink");
//                return false;
//            }
//
//
//
//            foreach($teamIDs as $teamID)
//            {
//                $teamID = mysql_real_escape_string($teamID, $link);
//
//                $sql = "INSERT INTO tdo_team_autorenew_history (teamid) VALUES ('$teamID')";
//                if (!mysql_query($sql, $link))
//                {
//                    error_log("TDOTeamAccount::addTeamsForAutorenewal() unable to add teams for autorenewal: " . mysql_error());
//                    mysql_query("ROLLBACK", $link);
//                    TDOUtil::closeDBLink($link);
//                    return false;
//                }
//            }
//
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("TDOTeamAccount::addTeamsForAutorenewal() couldn't commit transaction after adding teams:" . mysql_error());
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return false;
//            }
//
//            TDOUtil::closeDBLink($link);
//            return true;
//        }
	}


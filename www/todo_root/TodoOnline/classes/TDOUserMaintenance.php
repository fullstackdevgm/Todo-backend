<?php
//      TDOUserMaintenance
//      Class and methods to deal with marking users for maintenance for doing
//		things like removing duplicate tasks, deleting all user data, etc.
	
	define('USER_MAINTENANCE_OPERATION_TYPE_PURGE_DUPLICATE_TASKS', 1);
	define('USER_MAINTENANCE_OPERATION_TYPE_DELETE_DUPLICATE_TASKS', 2);
    
// include files
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');
	
class TDOUserMaintenance
{
	// Returns BOOL
	public static function isMaintenanceInProgressForUser($userid, $link = NULL)
	{
		if (empty($userid))
		{
			//			echo "TDOUserMaintenance::isMaintenanceInProgressForUser() called with empty userid\n";
			return true;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				//				echo "TDOUserMaintenance::isMaintenanceInProgressForUser() could not get DB connection\n";
				return true;
			}
		}
		
		$maintenanceInProgress = false;
		
		$userid = mysql_real_escape_string($userid, $link);
		$sql = "SELECT COUNT(*) FROM tdo_user_maintenance WHERE userid='$userid'";
		$response = mysql_query($sql, $link);
		if ($response)
		{
			$total = mysql_fetch_array($response);
			
			if ($total && isset($total[0]) && $total[0] > 0)
			{
				$maintenanceInProgress = true;
			}
		}
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return $maintenanceInProgress;
	}
	
	// Returns associative array with 'userid', 'operation_type', 'timestamp',
	// and 'daemonid' ... OR ... NULL if the user does not exist or if you
	// specified invalid parameters.
	public static function getMaintenanceInfoForUser($userid, $link = NULL)
	{
		if (empty($userid))
		{
//			echo "TDOUserMaintenance::getMaintenanceInfoForUser() called with empty userid\n";
			return NULL;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
//				echo "TDOUserMaintenance::getMaintenanceInfoForUser() could not get DB connection\n";
				return NULL;
			}
		}
		
		$userid = mysql_real_escape_string($userid, $link);
		$sql = "SELECT operation_type,timestamp,daemonid FROM tdo_user_maintenance WHERE userid='$userid'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
//			echo "TDOUserMaintenance::getMaintenanceInfoForUser() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return NULL;
		}
		
		$row = mysql_fetch_array($result);
		if (!$row)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
//			echo "TDOUserMaintenance::getMaintenanceInfoForUser() error fetching a row from the results: " . mysql_error() . "\n";
			return NULL;
		}
		
		$userInfo = array('userid' => $userid);
		if (isset($row['operation_type']))
			$userInfo['operationType'] = $row['operation_type'];
		if (isset($row['timestamp']))
			$userInfo['timestamp'] = $row['timestamp'];
		if (isset($row['daemonid']))
			$userInfo['daemonid'] = $row['daemonid'];
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return $userInfo;
	}
	
	
	// Returns BOOL
	public static function addUserForMaintenance($userid, $operationType, $link = NULL)
	{
		if (TDOUserMaintenance::isMaintenanceInProgressForUser($userid, $link))
		{
			return TDOUserMaintenance::updateUser($userid, $operationType, $link);
		}
		
		if (empty($userid))
		{
			echo "TDOUserMaintenance::addUserForMaintenance() called with empty userid\n";
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "TDOUserMaintenance::addUserForMaintenance() could not get DB connection\n";
				return false;
			}
		}
		
		$userid = mysql_real_escape_string($userid, $link);
		$timestamp = time();
		$sql = "INSERT INTO tdo_user_maintenance(userid,operation_type,timestamp) VALUES ('$userid', $operationType, $timestamp)";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			echo "TDOUserMaintenance::addUserForMaintenance() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return false;
		}
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return true;
	}
	
	
	// Returns BOOL
	public static function updateUser($userid, $operationType, $link = NULL)
	{
		if (empty($userid))
		{
			echo "TDOUserMaintenance::updateUser() called with empty userid\n";
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "TDOUserMaintenance::updateUser() could not get DB connection\n";
				return false;
			}
		}
		
		$userid = mysql_real_escape_string($userid, $link);
		$timestamp = time();
		$sql = "UPDATE tdo_user_maintenance SET operation_type=$operationType, timestamp=$timestamp WHERE userid='$userid'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			echo "TDOUserMaintenance::updateUser() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return false;
		}
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return true;
	}
	
	
	// Returns BOOL
	public static function markUserWithDaemon($daemonid, $operationType, $link = NULL)
	{
		// Don't mark another user for this daemon if a record is already marked.
		$markedUser = TDOUserMaintenance::getMarkedUserForDaemon($daemonid, $link);
		if (!empty($markedUser))
			return true;
		
		if (empty($daemonid))
		{
			echo "TDOUserMaintenance::markUserWithDaemon() called with empty daemonid\n";
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "TDOUserMaintenance::markUserWithDaemon() could not get DB connection\n";
				return false;
			}
		}
        
		$daemonid = mysql_real_escape_string($daemonid, $link);
		$sql = "UPDATE tdo_user_maintenance SET daemonid='$daemonid', operation_type=$operationType WHERE daemonid = '' OR daemonid IS NULL ORDER BY timestamp DESC LIMIT 1";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			echo "TDOUserMaintenance::markUserWithDaemon() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return false;
		}
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return true;
	}
	
	
	// Returns userid
	public static function getMarkedUserForDaemon($daemonid, $link = NULL)
	{
		if (empty($daemonid))
		{
			echo "TDOUserMaintenance::getMarkedUserForDaemon() called with empty daemonid\n";
			return NULL;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "TDOUserMaintenance::getMarkedUserForDaemon() could not get DB connection\n";
				return NULL;
			}
		}
		
		$daemonid = mysql_real_escape_string($daemonid, $link);
		$sql = "SELECT userid FROM tdo_user_maintenance WHERE daemonid='$daemonid' ORDER BY timestamp LIMIT 1"; // The ORDER BY/LIMIT is added here as a safeguard in case the table somehow becomes corrupt
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			echo "TDOUserMaintenance::getMarkedUserForDaemon() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return NULL;
		}
		
		$row = mysql_fetch_array($result);
		if (!$row)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
//			echo "TDOUserMaintenance::getMarkedUserForDaemon() error fetching a row from the results: " . mysql_error() . "\n";
			return NULL;
		}
		
		$userid = NULL;
		if (isset($row['userid']))
			$userid = $row['userid'];
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return $userid;
	}
	
	
	// Returns BOOL
	public static function removeUser($userid, $link = NULL)
	{
		if (empty($userid))
		{
			echo "TDOUserMaintenance::removeUser() called with empty userid\n";
			return false;
		}
		
		$closeLink = false;
		if ($link == NULL)
		{
			$closeLink = true;
			$link = TDOUtil::getDBLink();
			if (!$link)
			{
				echo "TDOUserMaintenance::removeUser() could not get DB connection\n";
				return false;
			}
		}
		
		$userid = mysql_real_escape_string($userid, $link);
		$sql = "DELETE FROM tdo_user_maintenance WHERE userid='$userid'";
		$result = mysql_query($sql, $link);
		if (!$result)
		{
			if ($closeLink == true)
				TDOUtil::closeDBLink($link);
			
			echo "TDOUserMaintenance::removeUser() error performing SQL query ($sql) with error: " . mysql_error() . "\n";
			return false;
		}
		
		if ($closeLink == true)
			TDOUtil::closeDBLink($link);
		
		return true;
	}

}
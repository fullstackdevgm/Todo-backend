<?php

//include_once 'Sabre/HTTP/includes.php';
include_once 'Sabre/DAV/includes.php';
include_once('TodoOnline/base_sdk.php');		
	
/**
 * TDODavPrincipal
 */
	
class TDODavPrincipal implements Sabre_DAVACL_IPrincipalBackend 
{

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only 
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can 
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname 
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV 
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     * 
     * @param string $prefixPath 
     * @return array 
     */
    public function getPrincipalsByPrefix($prefixPath)
	{
		if (!isset($_SERVER['PHP_AUTH_USER']))
		{
			return;
		}

		//error_log("getPrincipalsByPrefix was called");

		$pathArray = Sabre_DAV_URLUtil::splitPath($prefixPath);
		
		if($pathArray == NULL)
		{
			error_log("getPricipalByPath was unable to split the path: ".$prefixPath);
			return;
		}
		
		$prefix = $pathArray[0];
		if($prefix == NULL || $prefix == '')
			$prefix = $pathArray[1];
		
		if($prefix != "principals")
			return;


		$curUser = TDOUser::getUserForUsername($_SERVER['PHP_AUTH_USER']);
		if($curUser == false)
		{
			// cant' find current user
			return;
		}
		
//		$users = TDOUser::getAllUsers();
		
        $principals = array();

		$principals[] = array(
							  'id'  => $curUser->userId(),
							  'uri' => "principals/".$curUser->username(),
							  //'{DAV:}displayname' => $row['displayname']?$row['displayname']:basename($row['uri']),
							  //'{http://sabredav.org/ns}email-address' => $row['email'],
							  );
		
//		
//		
//		foreach($users as $aUser)		
//		{
//			$username = $aUser->username();
//			if(!isset($username))
//				$username = $aUser->userId();
//			
//			$principals[] = array(
//								  'id'  => $aUser->userId(),
//								  'uri' => "principals/".$username,
//								  //'{DAV:}displayname' => $row['displayname']?$row['displayname']:basename($row['uri']),
//								  //'{http://sabredav.org/ns}email-address' => $row['email'],
//								  );
//		}

		//error_log("getPrincipalsByPrefix is returning principles");
        return $principals;

    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from 
     * getPrincipalsByPrefix. 
     * 
     * @param string $path 
     * @return array 
     */
    public function getPrincipalByPath($path)
	{
		//error_log("getPrincipalByPath was called");
		
		$pathArray = Sabre_DAV_URLUtil::splitPath($path);

		if($pathArray == NULL)
		{
			error_log("getPricipalByPath was unable to split the path: ".$path);
			return;
		}
		
		$userid = $pathArray[1];

		
		if(TDOUser::existsUsername($userid))
		{
            $user = TDOUser::getUserForUsername($userid);
			if($user == false)
			{
				error_log("getPricipalByPath was unable to find the user: ".$userid." from path: ".$path);
				return;
			}
		}
		else
		{
            $user = TDOUser::getUserForUserId($userid);
			if($user == false)
			{
				error_log("getPricipalByPath was unable to find the user: ".$userid." from path: ".$path);
				return;
			}
		}
		
        return array(
					 'id'  => $user->userId(),
					 'uri' => "principals/".$userid,
					 //'{DAV:}displayname' => $row['displayname']?$row['displayname']:basename($row['uri']),
					 //'{http://sabredav.org/ns}email-address' => $row['email'],
        );
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is supplied as an array. Each key in the array is
     * a propertyname, such as {DAV:}displayname.
     *
     * Each value is the actual value to be updated. If a value is null, it
     * must be deleted.
     *
     * This method should be atomic. It must either completely succeed, or
     * completely fail. Success and failure can simply be returned as 'true' or
     * 'false'.
     *
     * It is also possible to return detailed failure information. In that case
     * an array such as this should be returned:
     *
     * array(
     *   200 => array(
     *      '{DAV:}prop1' => null,
     *   ),
     *   201 => array(
     *      '{DAV:}prop2' => null,
     *   ),
     *   403 => array(
     *      '{DAV:}prop3' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}prop4' => null,
     *   ),
     * );
     *
     * In this previous example prop1 was successfully updated or deleted, and
     * prop2 was succesfully created.
     *
     * prop3 failed to update due to '403 Forbidden' and because of this prop4
     * also could not be updated with '424 Failed dependency'.
     *
     * This last example was actually incorrect. While 200 and 201 could appear
     * in 1 response, if there's any error (403) the other properties should
     * always fail with 423 (failed dependency).
     *
     * But anyway, if you don't want to scratch your head over this, just
     * return true or false.
     *
     * @param string $path
     * @param array $mutations
     * @return array|bool
     */
    function updatePrincipal($path, $mutations)
    {
        return false;
    }
    
    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @return array
     */
    function searchPrincipals($prefixPath, array $searchProperties)
    {
        return false;
    }
    
    
    
    
    /**
     * Returns the list of members for a group-principal 
     * 
     * @param string $principal 
     * @return array 
     */
    public function getGroupMemberSet($principal)
	{
//        $principal = $this->getPrincipalByPath($principal);
//        if (!$principal) throw new Sabre_DAV_Exception('Principal not found');
//
//        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM `'.$this->groupMembersTableName.'` AS groupmembers LEFT JOIN `'.$this->tableName.'` AS principals ON groupmembers.member_id = principals.id WHERE groupmembers.principal_id = ?');
//        $stmt->execute(array($principal['id']));
//
        $result = array();
//        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//            $result[] = $row['uri'];
//        }
        return $result;
    
    }

    /**
     * Returns the list of groups a principal is a member of 
     * 
     * @param string $principal 
     * @return array 
     */
    public function getGroupMembership($principal)
	{
//        $principal = $this->getPrincipalByPath($principal);
//        if (!$principal) throw new Sabre_DAV_Exception('Principal not found');
//
//        $stmt = $this->pdo->prepare('SELECT principals.uri as uri FROM `'.$this->groupMembersTableName.'` AS groupmembers LEFT JOIN `'.$this->tableName.'` AS principals ON groupmembers.principal_id = principals.id WHERE groupmembers.member_id = ?');
//        $stmt->execute(array($principal['id']));
//
        $result = array();
//        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//            $result[] = $row['uri'];
//        }
        return $result;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's. 
     * 
     * @param string $principal 
     * @param array $members 
     * @return void
     */
    public function setGroupMemberSet($principal, array $members)
	{
        $result = array();
        return $result;		
//
//        // Grabbing the list of principal id's.
//        $stmt = $this->pdo->prepare('SELECT id, uri FROM `'.$this->tableName.'` WHERE uri IN (? ' . str_repeat(', ? ', count($members)) . ');');
//        $stmt->execute(array_merge(array($principal), $members));
//
//        $memberIds = array();
//        $principalId = null;
//
//        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//            if ($row['uri'] == $principal) {
//                $principalId = $row['id'];
//            } else {
//                $memberIds[] = $row['id'];
//            }
//        }
//        if (!$principalId) throw new Sabre_DAV_Exception('Principal not found');
//
//        // Wiping out old members
//        $stmt = $this->pdo->prepare('DELETE FROM `'.$this->groupMembersTableName.'` WHERE principal_id = ?;');
//        $stmt->execute(array($principalId));
//
//        foreach($memberIds as $memberId) {
//
//            $stmt = $this->pdo->prepare('INSERT INTO `'.$this->groupMembersTableName.'` (principal_id, member_id) VALUES (?, ?);');
//            $stmt->execute(array($principalId, $memberId));
//
//        }

    }

}

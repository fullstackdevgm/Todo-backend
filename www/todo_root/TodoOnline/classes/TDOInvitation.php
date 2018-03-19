<?php
	//      TDOInvitation
	//      Used to handle all user data
	
	// include files
	include_once('AWS/sdk.class.php');
	include_once('TodoOnline/base_sdk.php');
	
    define('INVITE_ONLY_LIST', 'e765e508-0858-4ee2-8d87-inviteonly47');
	
	class TDOInvitation extends TDODBObject
	{
		
		public function __construct()
		{
            parent::__construct();
			$this->set_to_default();      
		}
		
		public function set_to_default()
		{
            parent::set_to_default();
		}
		
		public static function deleteInvitation($invitationid)
		{
            if(!isset($invitationid))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOInvitation unable to get link");
                return false;
            }
            $invitationid = mysql_real_escape_string($invitationid, $link);
			$sql = "DELETE FROM tdo_invitations WHERE invitationid='$invitationid'";
			$response = mysql_query($sql, $link);
            if($response)
            {
                TDOUtil::closeDBLink($link);
                return true;
            }
            else
                error_log("PBInitation delete invitation failed: ".mysql_error());
			
            TDOUtil::closeDBLink($link);
			return false;
		}
		
		public function addInvitation()
		{
			if($this->listId() == NULL)
			{
				error_log("TDOInvitation::addInvitation failed because list was not set");
				return false;
			}
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            if($this->invitationId() == NULL)
                $this->setInvitationId(TDOUtil::uuid());
			
            $invitationid = mysql_real_escape_string($this->invitationId());
			$listid = mysql_real_escape_string($this->listId(), $link);
			$userid = mysql_real_escape_string($this->userId(), $link);
            $email = mysql_real_escape_string($this->email(), $link);
            if($this->invitedUserId() != null)
                $invitedUserId = mysql_real_escape_string($this->invitedUserId(), $link);
            else
                $invitedUserId = null;
            
            $timestamp = intval($this->timestamp());
            $membershipType = mysql_real_escape_string($this->membershipType(), $link);
            $fbid = mysql_real_escape_string($this->fbUserId(), $link);
            $fbRequestId = mysql_real_escape_string($this->fbRequestId(), $link);
			
			$sql = "INSERT INTO tdo_invitations (invitationid, listid, userid, email, invited_userid, timestamp, membership_type, fb_userid, fb_requestid) VALUES ('$invitationid', '$listid', '$userid', '$email', '$invitedUserId', $timestamp, $membershipType, '$fbid', '$fbRequestId')";
			
			$response = mysql_query($sql, $link);
			if($response)
            {
				

				// If we have a username here, log it, otherwise log the facebook name
				$userName = $this->email();
				if(!empty($userName))
				{
					$session = TDOSession::getInstance();
					TDOChangeLog::addChangeLog($this->listId(), $session->getUserId(), $this->invitationId(), $userName, ITEM_TYPE_INVITATION, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
				}
				else
                {
                    $invitedName = TDOFBUtil::getFBUserNameForFBUserId($fbid);

                    $session = TDOSession::getInstance();
                    TDOChangeLog::addChangeLog($listid, $session->getUserId(), $invitationid, $invitedName, ITEM_TYPE_INVITATION, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
                }
                
                TDOUtil::closeDBLink($link);
				return true;
            }
			else
			{
				error_log("TDOInvitation::addInvitation failed: ".mysql_error());
			}
            
            TDOUtil::closeDBLink($link);
            return false;
		}
		
        public function updateInvitation()
        {
            if($this->invitationId() == NULL)
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOInvitation unable to get DB link");
                return false;
            }
            
            //The timestamp is the time the invitation was sent, not the time it was last modified,
            //so we don't want to update it every time we update the invitation
            if($this->timestamp() == 0)
                $this->setTimestamp(time());
                
            $updateString = "UPDATE tdo_invitations SET timestamp=".intval($this->timestamp());
            
            //The listid, email, and fb_userid of an invitation cannot be updated once they are set, so don't
            //include those in this method
            
            if($this->userId() != NULL)
                $updateString .= ", userid='".mysql_real_escape_string($this->userId(), $link)."'";
            else
                $updateString .= ", userid=NULL";
                
            $updateString .= ", membership_type=".intval($this->membershipType());
                
            if($this->fbRequestId() != NULL)
                $updateString .= ", fb_requestid='".mysql_real_escape_string($this->fbRequestId(), $link)."'";
            else
                $updateString .= ", fb_requestid=NULL";
                
            $updateString .= " WHERE invitationid='".mysql_real_escape_string($this->invitationId(), $link)."'";
            
            
            if(mysql_query($updateString, $link) == false)
            {
                error_log("updateInvitation failed with error: ".mysql_error());
                TDOUtil::closeDBLink($link);
                return false;
            }
            
            TDOUtil::closeDBLink($link);
            return true;
        }
        
        public static function createInvitation($userid, $listid, $membershipType, $email=NULL)
        {
            if(!isset($listid))
                return false;
        
            $invitation = new TDOInvitation();
            $invitation->setListId($listid);
            if($email)
            {
                $invitation->setEmail($email);
                
                // Look up the email to see if we have a registered user with that email.
                // if we do put their user id in the invitation
                $invitedUserId = TDOUser::userIdForUserName($email);
                if($invitedUserId)
                {
                    $invitation->setInvitedUserId($invitedUserId);
                }
            }
			$invitation->setUserId($userid);
            $currentTime = time();
            $invitation->setTimestamp($currentTime);
            $invitation->setMembershipType($membershipType);
            if($invitation->addInvitation() == false)
            {
                return false;
            }
            
            return $invitation;
        }
		
        public static function getInvitationCountForList($listid)
        {
            if(empty($listid))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            $sql = "SELECT COUNT(invitationid) FROM tdo_invitations WHERE listid='".mysql_real_escape_string($listid, $link)."'";
            if($response = mysql_query($sql, $link))
            {
                if($row = mysql_fetch_array($response))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                }
            }
            else
                error_log("getInvitationCountForList failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }

        public static function getInvitationCountForInvitedUser($userid)
        {
            if(empty($userid))
                return false;
            
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            $sql = "SELECT COUNT(invitationid) FROM tdo_invitations WHERE invited_userid='".mysql_real_escape_string($userid, $link)."'";
            if($response = mysql_query($sql, $link))
            {
                if($row = mysql_fetch_array($response))
                {
                    if(isset($row['0']))
                    {
                        $count = $row['0'];
                        TDOUtil::closeDBLink($link);
                        return $count;
                    }
                }
            }
            else
                error_log("getInvitationCountForInvitedUser failed with error: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
            
        }
		
		public static function getInvitations($userid=NULL, $listid=NULL, $inviteduserid=NULL)
		{
           $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            $sql = "SELECT invitationid, listid, userid, email, invited_userid, timestamp, membership_type, fb_userid, fb_requestid FROM tdo_invitations";

			if( !empty($userid) || !empty($listid) || !empty($inviteduserid) )
				$sql = $sql." WHERE";

			if(!empty($userid))
				$sql = $sql." userid='$userid'";

			if(!empty($inviteduserid))
				$sql = $sql." invited_userid='$inviteduserid'";
            
			if(!empty($listid))
			{
				if(!empty($userid))
					$sql = $sql." AND ";
				$sql = $sql." listid='$listid'";
			}

            $sql = $sql ." ORDER BY timestamp";

			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $invitations = array();
				while($row = mysql_fetch_array($response))
                {
					$invitation = TDOInvitation::invitationFromRow($row);
                    $invitations[] = $invitation;    
                        
                }
                TDOUtil::closeDBLink($link);
                return $invitations;
			}
            else
                error_log("Get all invitations failed: ".mysql_error());
		
            TDOUtil::closeDBLink($link);
            return false;			
		}
        
        public static function getInvitationForInvitationId($invitationId)
        {
            if(!isset($invitationId))
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            
            $invitationId = mysql_real_escape_string($invitationId, $link);
            $sql = "SELECT invitationid, listid, userid, email, invited_userid, timestamp, membership_type, fb_userid, fb_requestid FROM tdo_invitations WHERE invitationid='$invitationId'";
			$response = mysql_query($sql, $link);
			
			if($response)
			{
                $row = mysql_fetch_array($response);
				if($row)
                {
                    $invitation = TDOInvitation::invitationFromRow($row);
                    TDOUtil::closeDBLink($link);
                    return $invitation;  
                }

			}
            else
                error_log("getInvitationForInvitationId failed: ".mysql_error());
		
            TDOUtil::closeDBLink($link);
            return false;			

        }

        public static function getInvitationForEmail($email, $userid = NULL, $listid = NULL)
        {
            $link = TDOUtil::getDBLink();
            if (!$link) {
                error_log("getInvitationForEmail unable to get link");
                return false;
            }

            $email = mysql_real_escape_string($email, $link);
            $sql = "SELECT invitationid, listid, userid, email, invited_userid, timestamp, membership_type, fb_userid, fb_requestid FROM tdo_invitations WHERE email='$email'";
            if (!empty($userid)) {
                $sql = $sql . " AND userid='$userid'";
            }
            if (!empty($listid)) {
                $sql = $sql . " AND listid='$listid'";
            }
            $response = mysql_query($sql, $link);

            if($response)
            {
                $invitations = array();
                while($row = mysql_fetch_array($response))
                {
                    $invitation = TDOInvitation::invitationFromRow($row);
                    $invitations[] = $invitation;

                }
                TDOUtil::closeDBLink($link);
                return $invitations;
            }
            else
                error_log("getInvitationForEmail failed: ".mysql_error());

            TDOUtil::closeDBLink($link);
            return false;

        }
        
        public static function invitationFromRow($row)
        {
            $invitation = new TDOInvitation();
            
            if(isset($row['invitationid']))
                $invitation->setInvitationId($row['invitationid']);
            if(isset($row['listid']))
                $invitation->setListId($row['listid']);
            if(isset($row['userid']))
                $invitation->setUserId($row['userid']);
            if(isset($row['email']))
                $invitation->setEmail($row['email']);
            if(isset($row['invited_userid']))
                $invitation->setInvitedUserId($row['invited_userid']);
            if(isset($row['timestamp']))
                $invitation->setTimestamp($row['timestamp']);
            if(isset($row['membership_type']))
                $invitation->setMembershipType($row['membership_type']);
            if(isset($row['fb_userid']))
                $invitation->setFBUserId($row['fb_userid']);
            if(isset($row['fb_requestid']))
                $invitation->setFBRequestId($row['fb_requestid']);
                
            return $invitation;
        }
		
		public static function getListidForInvitation($link, $invitationId)
		{
            if(!isset($invitationId))
                return false;
                
            if(!$link)
            {   
                error_log("TDOInvitation unable to get link");
                return false;               
            }
            
            $invitationId = mysql_real_escape_string($invitationId, $link);
            $sql = "SELECT listid FROM tdo_invitations where invitationid='$invitationId'";
			
            $response = mysql_query($sql, $link);
            
			if($response)
			{
				$resultsArray = mysql_fetch_array($response);
                if($resultsArray && isset($resultsArray['listid']))
                {
                    return $resultsArray['listid'];
                }
			}
            else
                error_log("getListidForInvitation failed: ".mysql_error());
		
            return false;			
	
		}
		
		public static function deleteInvitations($invitationids)
		{
            if(!isset($invitationids))
                return false;
                
            $link = TDOUtil::getDBLink();
            if(!$link) 
            {
                error_log("TDOInvitation unable to get link");
               return false;
            }
     
            foreach($invitationids as $invitationid)
            {
                $invitationid = mysql_real_escape_string($invitationid, $link);
                $sql = "DELETE FROM tdo_invitations WHERE invitationid='$invitationid'";
                if(!mysql_query($sql, $link))
                {
                    error_log("Unable to delete invitation $invitationid");
                }
            }
            TDOUtil::closeDBLink($link);
            return true;

		}
		
        public static function getInvitationForRequestIdAndFacebookId($requestid, $facebookid)
        {
            if(empty($requestid) || empty($facebookid))
            {
                return false;
            }
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("TDOInvitation unable to get link");
                return false;
            }
            
            $requestid = mysql_real_escape_string($requestid, $link);
            $facebookid = mysql_real_escape_string($facebookid, $link);
            $sql = "SELECT * FROM tdo_invitations WHERE fb_userid='$facebookid' AND fb_requestid='$requestid'";
            
             $result = mysql_query($sql, $link);
             if($result)
             {
                $row = mysql_fetch_array($result);
                if($row)
                {
                    $invitation = TDOInvitation::invitationFromRow($row);
                    TDOUtil::closeDBLink($link);
                    return $invitation;
                }
             }
             else
                error_log("getInvitationForRequestId failed: ".mysql_error());
            
            TDOUtil::closeDBLink($link);
            return false;
        }
    
		
        public function invitationId()
        {
            if(empty($this->_publicPropertyArray['invitationid']))
                return NULL;
            else
                return $this->_publicPropertyArray['invitationid'];
        }
        
		public function setInvitationId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['invitationid']);
            else
                $this->_publicPropertyArray['invitationid'] = $val;
		}
		
        public function listId()
        {
            if(empty($this->_publicPropertyArray['listid']))
                return NULL;
            else
                return $this->_publicPropertyArray['listid'];
        }
        
		public function setListId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['listid']);
            else
                $this->_publicPropertyArray['listid'] = $val;
		}


        public function userId()
        {
            if(empty($this->_publicPropertyArray['userid']))
                return NULL;
            else
                return $this->_publicPropertyArray['userid'];
        }
        
		public function setUserId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['userid']);
            else
                $this->_publicPropertyArray['userid'] = $val;
		}
        
        public function email()
        {
            if(empty($this->_publicPropertyArray['email']))
                return NULL;
            else
                return $this->_publicPropertyArray['email'];
        }
        
		public function setEmail($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['email']);
            else
                $this->_publicPropertyArray['email'] = $val;
		}

        public function invitedUserId()
        {
            if(empty($this->_publicPropertyArray['invitedUserId']))
                return NULL;
            else
                return $this->_publicPropertyArray['invitedUserId'];
        }
        
		public function setInvitedUserId($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['invitedUserId']);
            else
                $this->_publicPropertyArray['invitedUserId'] = $val;
		}

        public function membershipType()
        {
            if(empty($this->_publicPropertyArray['membershiptype']))
                return LIST_MEMBERSHIP_VIEWER;
            else
                return $this->_publicPropertyArray['membershiptype'];
        }
        
		public function setMembershipType($val)
		{
			if(empty($val))
                unset($this->_publicPropertyArray['membershiptype']);
            else
                $this->_publicPropertyArray['membershiptype'] = $val;
		}
        
        public function fbUserId()
        {
            if(empty($this->_publicPropertyArray['fbid']))
                return NULL;
            else
                return $this->_publicPropertyArray['fbid'];            
        }
        
        public function setFBUserId($val)
        {
			if(empty($val))
                unset($this->_publicPropertyArray['fbid']);
            else
                $this->_publicPropertyArray['fbid'] = $val;            
        }
        
        public function fbRequestId()
        {
            if(empty($this->_publicPropertyArray['fb_requestid']))
                return NULL;
            else
                return $this->_publicPropertyArray['fb_requestid'];            
        }
        
        public function setFBRequestId($val)
        {
			if(empty($val))
                unset($this->_publicPropertyArray['fb_requestid']);
            else
                $this->_publicPropertyArray['fb_requestid'] = $val;            
        }
        
        
        public function getPropertiesArray()
        {
            $this->_publicPropertyArray['readabledate'] =  TDOUtil::humanReadableStringFromTimestamp($this->timestamp());
			$this->_publicPropertyArray['timestamp'] = $this->timestamp();
            $this->_publicPropertyArray['inviter'] = TDOUser::displayNameForUserId($this->userId());
            
            $invitedName = $this->email();
            if(empty($invitedName))
            {
                $invitedName = TDOFBUtil::getFBUserNameForFBUserId($this->fbUserId());
            }
            
            if($this->fbUserId() != NULL)
            {
                $this->_publicPropertyArray['imgurl'] = "https://graph.facebook.com/".$this->fbUserId()."/picture";
            }

            $this->_publicPropertyArray['invitee'] = $invitedName;
            
            $listName = TDOList::nameForListId($this->listId());
            $this->_publicPropertyArray['listName'] = $listName;
            
            return $this->_publicPropertyArray;
        }
        
	}


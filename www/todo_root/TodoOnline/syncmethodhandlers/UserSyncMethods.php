<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	
    
	if(!$session->isLoggedIn())
	{
		error_log("UserSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("UserSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    
    function getUserArrayForUser($userId, $listId = NULL)
    {
        $userArray = array();
        
//        $fbId = TDOUser::facebookIdForUserId($userId);
//        if($fbId)
//        {
//            $userPicUrl = 'https://graph.facebook.com/'.$fbId.'/picture';
//        }
        $user = TDOUser::getUserForUserId($userId);


        //This is the @1x version of the image
        $imgUrl = $user->fullImageURL(false);
        if($imgUrl != NULL)
            $userArray['imgurl'] = $imgUrl;
        
        $largeImgUrl = $user->fullImageURL();
        if($largeImgUrl != NULL)
            $userArray['imgurl_2x'] = $largeImgUrl;
        
        $userArray['name'] = $user->displayName();
        $userArray['id'] = $userId;
	$userArray['email'] = $user->username();
        $userListArray = array();
        if($listId != NULL)
            $userListArray[] = $listId;
        $userArray['lists'] = $userListArray;
        return $userArray;
    }

    
    if($method == "getUsers")
    {
		$jsonResponse = array();
        $usersArray = array();
        
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);

        $listsJSON = array();
        
        $lists = TDOList::getListsForUser($session->getUserId());
        
        foreach($lists as $list)
        {
            if($list->listId() == $userInbox)
                continue;

            $userIds = TDOList::getEditingMembersForlistid($list->listId());
            
            foreach ($userIds as $userId)
            {
                if($userId == $session->getUserId())
                    continue;
                    
                if(array_key_exists($userId, $usersArray))
                {
                    $userArray = $usersArray[$userId];
                    $userListArray = $userArray['lists'];
                    $userListArray[] = $list->listId();
                    $userArray['lists'] = $userListArray;
                    $usersArray[$userId] = $userArray;
                }
                else
                {
                    $userArray = getUserArrayForUser($userId, $list->listId());
                    $usersArray[$userId] = $userArray;
//                    array_push($usersArray, $userArray);
                }
            }
        }
        
        $meUserArray = getUserArrayForUser($session->getUserId());
        
        $jsonResponse['users'] = $usersArray;
        $jsonResponse['me'] = $meUserArray;
		
		echo json_encode($jsonResponse);
        TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId());
    }

    
?>

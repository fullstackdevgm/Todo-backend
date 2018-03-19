<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	
    include_once('TodoOnline/DBConstants.php');
    
	if(!$session->isLoggedIn())
	{
		error_log("HandleListSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("HandleListSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    
    if($method == "getLists")
    {
		$jsonResponse = array();
		
        $userInbox = TDOList::getUserInboxId($session->getUserId(), false);

        $listsJSON = array();
        
        $lists = TDOList::getListsForUser($session->getUserId());
        
        foreach($lists as $list)
        {
            if($list->listId() == $userInbox)
                continue;

            array_push($listsJSON, $list->getPropertiesArrayWithUserSettings($session->getUserId()));
        }
        
        $jsonResponse['lists'] = $listsJSON;
        

        $specialListsJSON = array();
        
//        define('ALL_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829F829ALL');
//        define('FOCUS_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829E3FOCUS');
//        define('STARRED_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829STARRED');
//        define('UNFILED_LIST_ID', '9F6338F5-94C7-4B04-8E24-8F829UNFILED');        
        // get the All, Focus, Starred, and Inbox user settings
        $pArray = array();

        $listSettings = TDOListSettings::getListSettingsForUser(ALL_LIST_ID, $session->getUserId());
        if($listSettings)
        {
            $pArray['listid'] = ALL_LIST_ID;
            
            $color = $listSettings->color();
            if($color)
                $pArray['color'] = $color;
                    
            $iconName = $listSettings->iconName();
            if($iconName)
                $pArray['iconName'] = $iconName;
                    
            $pArray['sortOrder'] = $listSettings->sortOrder();
			$pArray['sortType'] = $listSettings->sortType();
			$pArray['defaultDueDate'] = $listSettings->defaultDueDate();
            
            array_push($specialListsJSON, $pArray);
        }

        $pArray = array();
        $listSettings = TDOListSettings::getListSettingsForUser(FOCUS_LIST_ID, $session->getUserId());
        if($listSettings)
        {
            $pArray['listid'] = FOCUS_LIST_ID;
            
            $color = $listSettings->color();
            if($color)
                $pArray['color'] = $color;
            
            $iconName = $listSettings->iconName();
            if($iconName)
                $pArray['iconName'] = $iconName;
            
            $pArray['sortOrder'] = $listSettings->sortOrder();
			$pArray['sortType'] = $listSettings->sortType();
			$pArray['defaultDueDate'] = $listSettings->defaultDueDate();
			
            array_push($specialListsJSON, $pArray);
        }

        $pArray = array();
        $listSettings = TDOListSettings::getListSettingsForUser(STARRED_LIST_ID, $session->getUserId());
        if($listSettings)
        {
            $pArray['listid'] = STARRED_LIST_ID;
            
            $color = $listSettings->color();
            if($color)
                $pArray['color'] = $color;
            
            $iconName = $listSettings->iconName();
            if($iconName)
                $pArray['iconName'] = $iconName;
            
            $pArray['sortOrder'] = $listSettings->sortOrder();
			$pArray['sortType'] = $listSettings->sortType();
			$pArray['defaultDueDate'] = $listSettings->defaultDueDate();
			
            array_push($specialListsJSON, $pArray);
        }

        $pArray = array();
        $listSettings = TDOListSettings::getListSettingsForUser($userInbox, $session->getUserId());
        if($listSettings)
        {
            // this is not the actual list ID but to make it easy for the
            // clients to sync, we're changing it to this ID
            $pArray['listid'] = UNFILED_LIST_ID;
            
            $color = $listSettings->color();
            if($color)
                $pArray['color'] = $color;
            
            $iconName = $listSettings->iconName();
            if($iconName)
                $pArray['iconName'] = $iconName;
            
            $pArray['sortOrder'] = $listSettings->sortOrder();
			$pArray['sortType'] = $listSettings->sortType();
			$pArray['defaultDueDate'] = $listSettings->defaultDueDate();
			
            array_push($specialListsJSON, $pArray);
        }
        
        $jsonResponse['speciallists'] = $specialListsJSON;
		
		echo json_encode($jsonResponse);
        TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId(), 0, NULL);
    }
    else if($method == "changeLists")
    {
        $link = TDOUtil::getDBLink();
        
        if(empty($link))
        {
            error_log("syncLists failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }


        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("syncLists failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }
        
        
        $lastErrorCode = 0;
        $lastErrorDesc = NULL;
        
        $userModifiedLists = array();
        
        $responseArray = array();
        $resultsArray = array();
        
        // Lists are going to be posted in the variables: addLists, updateLists, and deleteLists
        // The values will be a JSON encoded array of list properties like this:
        // $_POST['addLists'] = "[{"tmpListId":"AFSDS2345", "listName":"New List"}, {"tmpListId":"DF2345677", "listName":"New List 2"}]"
        // $_POST['updateLists'] = "[{"listId":"AFSDS2345", "listName":"New List"}, {"listId":"DF2345677", "listName":"New List 2"}]"
        // $_POST['deleteLists'] = "[{"listId":"AFSDS2345"}, {"listId":"DF2345677"}]"

        // The response will be a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpListId":"AFSDS2345", "listId":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //   "listHash":"234523423132"}]
 
        if(isset($_POST['addLists']) == true)
        {
            
            $addListArray = json_decode($_POST['addLists'], true);

            if( ($addListArray === NULL) || empty($addListArray) )
            {
                error_log("changeLists had addLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncLists failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $addResults = array();
            
            
            foreach($addListArray as $listToAdd)
            {
                $addedListArray = array();

                if(empty($listToAdd['tmpListId']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " tmpListId was missing.";
                    $addedListArray['errorCode'] = $lastErrorCode;
                    $addedListArray['errorDesc'] = $lastErrorDesc;
                    $addResults[] = $addedListArray;
                    continue;
                }
                    
                $addedListArray['tmpListId'] = $listToAdd['tmpListId'];
                $tmpListId = $listToAdd['tmpListId'];
                
                if(empty($listToAdd['listName']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listName was missing.";
                    $addedListArray['errorCode'] = $lastErrorCode;
                    $addedListArray['errorDesc'] = $lastErrorDesc;
                }
                else
                {
                    $listName = $listToAdd['listName'];
                    
                    $newList = new TDOList();
                    $newList->setName($listName);

                    $newList->setCreator($user->userId());
                    
                    if($newList->addList($user->userId(), NULL, $link))
                    {
                        $addedListArray['listId'] = $newList->listId();
                        $userModifiedLists[] = $newList->listId();
                        
						if (isset($listToAdd['color']) || isset($listToAdd['iconName']) || isset($listToAdd['sortOrder']) || isset($listToAdd['sortType']) || isset($listToAdd['defaultDueDate']))
						{
                            $listSettings = TDOListSettings::getListSettingsForUser($newList->listId(), $session->getUserId(), $link);
                            if($listSettings)
                            {
								if(isset($listToAdd['color']))
								{
									$listSettings->setColor($listToAdd['color']);
								}
								if(isset($listToAdd['iconName']))
								{
									$listSettings->setIconName($listToAdd['iconName']);
								}
								if(isset($listToAdd['sortOrder']))
								{
									$listSettings->setSortOrder($listToAdd['sortOrder']);
								}
								if(isset($listToAdd['sortType']))
								{
									$listSettings->setSortType($listToAdd['sortType']);
								}
								if(isset($listToAdd['defaultDueDate']))
								{
									$listSettings->setDefaultDueDate($listToAdd['defaultDueDate']);
								}
									
                                if(!$listSettings->updateListSettings($newList->listId(), $session->getUserId(), $link))
                                {
                                    error_log("Unable to update list settings when trying to set the color, iconName, sortOrder, sortType, or defaultDueDate");
                                }
                            }
                            else
                            {
                                error_log("No List settings for the list were found");
                            }
						}
                    }
                    else
                    {
                        $lastErrorCode = ERROR_CODE_ERROR_ADDING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_ADDING_OBJECT;
                        $addedListArray['errorCode'] = $lastErrorCode;
                        $addedListArray['errorDesc'] = $lastErrorDesc;
                    }
                }
                
                $addResults[] = $addedListArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncLists failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['added'] = $addResults;
        }
        
        if(isset($_POST['updateLists']) == true)
        {
            $updateResults = array();

            $updateListArray = json_decode($_POST['updateLists'], true);
            
            if( ($updateListArray === NULL) || empty($updateListArray) )
            {
                error_log("changeLists had updateLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncLists failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($updateListArray as $listToUpdate)
            {
                $updateListArray = array();
                
                if(empty($listToUpdate['listId']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listId was missing.";
                    $updateListArray['errorCode'] = $lastErrorCode;
                    $updateListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updateListArray;
                    continue;
                }
                
                $listId = $listToUpdate['listId'];
                $updateListArray['listId'] = $listId;
                
                $list = TDOList::getListForListid($listId, $link);
                if(empty($list))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND;
                    $updateListArray['errorCode'] = $lastErrorCode;
                    $updateListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updateListArray;
                    continue;
                }
                
                if(TDOList::userCanEditList($listId, $session->getUserId(), $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;
                    $updateListArray['errorCode'] = $lastErrorCode;
                    $updateListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updateListArray;
                    continue;
                }
                
                if(empty($listToUpdate['listName']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listName was missing.";
                    $updateListArray['errorCode'] = $lastErrorCode;
                    $updateListArray['errorDesc'] = $lastErrorDesc;
                }
                else
                {
                    $listName = $listToUpdate['listName'];
                    
                    $list->setName($listName);
                    
                    if($list->updateList($session->getUserId(), $link) == false)
                    {
                        $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;
                        $updateListArray['errorCode'] = $lastErrorCode;
                        $updateListArray['errorDesc'] = $lastErrorDesc;
                    }
                    else
                    {
                        $userModifiedLists[] = $list->listId();
						
						if (isset($listToUpdate['color']) || isset($listToUpdate['iconName']) || isset($listToUpdate['sortOrder']) || isset($listToUpdate['sortType']) || isset($listToUpdate['defaultDueDate']))
						{
                            $listSettings = TDOListSettings::getListSettingsForUser($list->listId(), $session->getUserId(), $link);
                            if($listSettings)
                            {
								if(isset($listToUpdate['color']))
								{
									$listSettings->setColor($listToUpdate['color']);
								}
								if(isset($listToUpdate['iconName']))
								{
									$listSettings->setIconName($listToUpdate['iconName']);
								}
								if(isset($listToUpdate['sortOrder']))
								{
									$listSettings->setSortOrder($listToUpdate['sortOrder']);
								}
								if(isset($listToUpdate['sortType']))
								{
									$listSettings->setSortType($listToUpdate['sortType']);
								}
								if(isset($listToUpdate['defaultDueDate']))
								{
									$listSettings->setDefaultDueDate($listToUpdate['defaultDueDate']);
								}
								
                                if(!$listSettings->updateListSettings($list->listId(), $session->getUserId(), $link))
                                {
                                    error_log("Unable to update list settings when trying to set the color, iconName, sortOrder, sortType, or defaultDueDate");
                                }
                            }
                            else
                            {
                                error_log("No List settings for the list were found");
                            }
						}
                    }
                }
                
                $updateResults[] = $updateListArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncLists failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteLists']) == true)
        {
            $deleteResults = array();
            
            $deleteListArray = json_decode($_POST['deleteLists'], true);
            
            if( ($deleteListArray === NULL) || empty($deleteListArray) )
            {
                error_log("changeLists had deleteLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncLists failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($deleteListArray as $listToDelete)
            {
                $deleteListArray = array();
                
                if(empty($listToDelete['listId']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listId was missing.";
                    $deleteListArray['errorCode'] = $lastErrorCode;
                    $deleteListArray['errorDesc'] = $lastErrorDesc;
                    $deleteResults[] = $deleteListArray;
                    continue;
                }
                
                $listId = $listToDelete['listId'];
                $deleteListArray['listId'] = $listId;
                
                if(TDOList::getRoleForUser($listId, $session->getUserId(), $link) != LIST_MEMBERSHIP_OWNER)
                {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;
                    $deleteListArray['errorCode'] = $lastErrorCode;
                    $deleteListArray['errorDesc'] = $lastErrorDesc;
                    $deleteResults[] = $deleteListArray;
                    continue;
                }

                if(TDOList::deleteList($listId, $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;
                    $deleteListArray['errorCode'] = $lastErrorCode;
                    $deleteListArray['errorDesc'] = $lastErrorDesc;
                }
                else
                {
                    $userModifiedLists[] = $listId;
                }
                
                $deleteResults[] = $deleteListArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncLists failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['deleted'] = $deleteResults;
        }
        
        if(isset($_POST['updateSpecialLists']) == true)
        {
            $updateResults = array();
            
            $updateSpecialListArray = json_decode($_POST['updateSpecialLists'], true);
            
            if( ($updateSpecialListArray === NULL) || empty($updateSpecialListArray) )
            {
                error_log("changeLists had updateSpecialLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
            foreach($updateSpecialListArray as $specialListToUpdate)
            {
                $updatedListArray = array();
                
                if(empty($specialListToUpdate['listId']))
                {
                    // if we don't have a tmpListId we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " listId was missing.";
                    $updatedListArray['errorCode'] = $lastErrorCode;
                    $updatedListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updatedListArray;
                    continue;
                }
                
                $listId = $specialListToUpdate['listId'];
                $updatedListArray['listId'] = $listId;
                
                if($listId != ALL_LIST_ID && $listId != FOCUS_LIST_ID &&
                   $listId != STARRED_LIST_ID && $listId != UNFILED_LIST_ID)
                {
                    // This method can only be called on special lists
                    // if the list ID passed is not a special list then
                    // Error.
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " attempting to update a non-special list.";
                    $updatedListArray['errorCode'] = $lastErrorCode;
                    $updatedListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updatedListArray;
                    continue;
                }

                if($listId == UNFILED_LIST_ID)
                {
                    $listId = TDOList::getUserInboxId($session->getUserId(), false);
                    if(empty($listId))
                    {
                        $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                        $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " attempting to update a non-special list.";
                        $updatedListArray['errorCode'] = $lastErrorCode;
                        $updatedListArray['errorDesc'] = $lastErrorDesc;
                        $updateResults[] = $updatedListArray;
                        continue;
                    }
                }
                
                
                $listSettings = TDOListSettings::getListSettingsForUser($listId, $session->getUserId());
                if(empty($listSettings))
                {
                    // we need to add this list, not update it

                    $listSettings = new TDOListSettings();
                    if(!$listSettings->addListSettings($listId, $session->getUserId(), $link))
                    {
                        // This method can only be called on special lists
                        // if the list ID passed is not a special list then
                        // Error.
                        $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT . " adding list setting.";
                        $updatedListArray['errorCode'] = $lastErrorCode;
                        $updatedListArray['errorDesc'] = $lastErrorDesc;
                        $updateResults[] = $updatedListArray;
                        continue;
                    }
                }

                // check again to make sure we have list settings now
                if(empty($listSettings))
                {
                    // This method can only be called on special lists
                    // if the list ID passed is not a special list then
                    // Error.
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND . " adding list setting.";
                    $updatedListArray['errorCode'] = $lastErrorCode;
                    $updatedListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updatedListArray;
                    continue;
                }
                
                if(isset($specialListToUpdate['color']))
                {
                    $listSettings->setColor($specialListToUpdate['color']);
                }
                if(isset($specialListToUpdate['iconName']))
                {
                    $listSettings->setIconName($specialListToUpdate['iconName']);
                }
                if(isset($specialListToUpdate['sortOrder']))
                {
                    $listSettings->setSortOrder($specialListToUpdate['sortOrder']);
                }
				if(isset($specialListToUpdate['sortType']))
				{
					$listSettings->setSortType($specialListToUpdate['sortType']);
				}
				if(isset($specialListToUpdate['defaultDueDate']))
				{
					$listSettings->setDefaultDueDate($specialListToUpdate['defaultDueDate']);
				}
				
                if(!$listSettings->updateListSettings($listId, $session->getUserId(), $link))
                {
                    // This method can only be called on special lists
                    // if the list ID passed is not a special list then
                    // Error.
                    $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT . " adding list setting.";
                    $updatedListArray['errorCode'] = $lastErrorCode;
                    $updatedListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updatedListArray;
                    continue;
                }
                
                $updateResults[] = $updatedListArray;
            }

            $resultsArray['updatedSpecialLists'] = $updateResults;
        }
        
        
        $responseArray['results'] = $resultsArray;
        $actionsArray = array();
        
        $listTimeStamp = TDOList::listHashForUser($session->getUserId(), $link);
        if($listTimeStamp != NULL)
            $responseArray['listHash'] = $listTimeStamp;
        

        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);
            
            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_LISTS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_LISTS;
            
            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the lists from the server failed with error: " . json_last_error() . " Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;
                
                error_log("syncLists failed to commit transaction");
                outputSyncError($lastErrorCode, $lastErrorDesc);
                
                mysql_query("ROLLBACK", $link);
            }
            else
                echo $jsonResponse;
        }
        
        
        TDOUtil::closeDBLink($link);        

        
        if($lastErrorCode != 0)
            TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId(), $lastErrorCode, $method . ": " . $lastErrorDesc);
        else
            TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId());
    }
    
?>

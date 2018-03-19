<?php

	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');
	include_once('TodoOnline/php/SessionHandler.php');
    include_once('TodoOnline/DBConstants.php');

	if(!$session->isLoggedIn())
	{
		error_log("HandleSyncListSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}

	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("HandleSmartListSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}

    if($method == "getSmartLists")
    {
		$jsonResponse = array();

        $listsJSON = array();

        $lists = TDOSmartList::getSmartListsForUser($session->getUserId());

        foreach($lists as $list)
        {
			$listProps = array();
			if ($list->listId()) {
				$listProps['listId'] = $list->listId();
			}
			if ($list->name()) {
				$listProps['name'] = $list->name();
			}
			if ($list->color()) {
				$listProps['color'] = $list->color();
			}
			if ($list->iconName()) {
				$listProps['iconName'] = $list->iconName();
			}
			if ($list->sortOrder()) {
				$listProps['sortOrder'] = $list->sortOrder();
			}
			if ($list->jsonFilter()) {
				$listProps['jsonFilter'] = $list->jsonFilter();
			}
			if ($list->sortType()) {
				$listProps['sortType'] = $list->sortType();
			}
			if ($list->defaultDueDate()) {
				$listProps['defaultDueDate'] = $list->defaultDueDate();
			}
			if ($list->defaultList()) {
				$listProps['defaultList'] = $list->defaultList();
			}
			if ($list->excludedListIDs()) {
				$listIDs = implode(',', $list->excludedListIDs());
				$listProps['excludedListIDs'] = $listIDs;
			}
			if ($list->completedTasksFilter()) {
				$filter = $list->completedTasksFilter();
				$listProps['completedTasksFilter'] = json_encode($filter->dictionaryRepresentation());
			}

//            array_push($listsJSON, $list->getPropertiesArray());
			$listsJSON[] = $listProps;
        }

        $jsonResponse['smartLists'] = $listsJSON;

		echo json_encode($jsonResponse);
        TDODevice::updateDeviceForUserAndSession($session->getUserId(), $session->getSessionId(), 0, NULL);
    }
    else if($method == "changeSmartLists")
    {
        $link = TDOUtil::getDBLink();

        if(empty($link))
        {
            error_log("changeSmartLists failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }

        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("changeSmartLists failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }

        $lastErrorCode = 0;
        $lastErrorDesc = NULL;

        $userModifiedLists = array();

        $responseArray = array();
        $resultsArray = array();

        // Smart Lists are posted in the variables: addSmartLists, updateSmartLists, and deleteSmartLists

        // The response is a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpListId":"AFSDS2345", "listId":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //   "listHash":"234523423132"}]

        if(isset($_POST['addSmartLists']) == true)
        {
            $addListArray = json_decode($_POST['addSmartLists'], true);

            if( ($addListArray === NULL) || empty($addListArray) )
            {
                error_log("changeSmartLists had addSmartLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }

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

                    $newList = new TDOSmartList();
                    $newList->setName($listName);

					if(isset($listToAdd['color']))
					{
						$newList->setColor($listToAdd['color']);
					}
					if(isset($listToAdd['iconName']))
					{
						$newList->setIconName($listToAdd['iconName']);
					}
					if(isset($listToAdd['sortOrder']))
					{
						$newList->setSortOrder($listToAdd['sortOrder']);
					}
					if(isset($listToAdd['jsonFilter']))
					{
						$newList->setJsonFilter($listToAdd['jsonFilter']);
					}
					if(isset($listToAdd['sortType']))
					{
						$newList->setSortType($listToAdd['sortType']);
					}
					if(isset($listToAdd['defaultDueDate']))
					{
						$newList->setDefaultDueDate($listToAdd['defaultDueDate']);
					}
					if(isset($listToAdd['defaultList']))
					{
						$newList->setDefaultList($listToAdd['defaultList']);
					}
					if(isset($listToAdd['excludedListIDs']))
					{
						$listsA = explode(',', $listToAdd['excludedListIDs']);
						$newList->setExcludedListIDs($listsA);
					}
					if(isset($listToAdd['completedTasksFilter']))
					{
						$completedTasksA = json_decode($listToAdd['completedTasksFilter'], true);
						if ($completedTasksA) {
							$completedTasksFilter = new TDOSmartListCompletedTasksFilter($completedTasksA);
							if ($completedTasksFilter) {
								$newList->setCompletedTasksFilter($completedTasksFilter);
							}
						}
					}



                    if($newList->addSmartList($user->userId(), $link))
                    {
                        $addedListArray['listId'] = $newList->listId();
                        $userModifiedLists[] = $newList->listId();
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

            $resultsArray['added'] = $addResults;
        }

        if(isset($_POST['updateSmartLists']) == true)
        {
            $updateResults = array();

            $updateListArray = json_decode($_POST['updateSmartLists'], true);

            if( ($updateListArray === NULL) || empty($updateListArray) )
            {
                error_log("changeSmartLists had updateSmartLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }

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

                $list = TDOSmartList::getSmartListForListid($listId, $link);
                if(empty($list))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND;
                    $updateListArray['errorCode'] = $lastErrorCode;
                    $updateListArray['errorDesc'] = $lastErrorDesc;
                    $updateResults[] = $updateListArray;
                    continue;
                }

                if(TDOSmartList::userCanEditSmartList($listId, $session->getUserId(), $link) == false)
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

					if (isset($listToUpdate['color'])) {
						$list->setColor($listToUpdate['color']);
					}
					if (isset($listToUpdate['iconName'])) {
						$list->setIconName($listToUpdate['iconName']);
					}
					if (isset($listToUpdate['sortOrder'])) {
						$list->setSortOrder($listToUpdate['sortOrder']);
					}
					if (isset($listToUpdate['jsonFilter'])) {
						$list->setJsonFilter($listToUpdate['jsonFilter']);
					}
					if (isset($listToUpdate['sortType'])) {
						$list->setSortType($listToUpdate['sortType']);
					}
					if (isset($listToUpdate['defaultDueDate'])) {
						$list->setDefaultDueDate($listToUpdate['defaultDueDate']);
					}
					if (isset($listToUpdate['defaultList'])) {
						$list->setDefaultList($listToUpdate['defaultList']);
					}
					if (isset($listToUpdate['excludedListIDs'])) {
						$listsA = explode(',', $listToUpdate['excludedListIDs']);
						$list->setExcludedListIDs($listsA);
					}
					if (isset($listToUpdate['completedTasksFilter'])) {
						$completedTasksA = json_decode($listToUpdate['completedTasksFilter'], true);
						if ($completedTasksA) {
							$completedTasksFilter = new TDOSmartListCompletedTasksFilter($completedTasksA);
							if ($completedTasksFilter) {
								$list->setCompletedTasksFilter($completedTasksFilter);
							}
						}
					}

                    if($list->updateSmartList($session->getUserId(), $link) == false)
                    {
                        $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;
                        $updateListArray['errorCode'] = $lastErrorCode;
                        $updateListArray['errorDesc'] = $lastErrorDesc;
                    }
                    else
                    {
                        $userModifiedLists[] = $list->listId();
                    }
                }

                $updateResults[] = $updateListArray;
            }

            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteSmartLists']) == true)
        {
            $deleteResults = array();

            $deleteListArray = json_decode($_POST['deleteSmartLists'], true);

            if( ($deleteListArray === NULL) || empty($deleteListArray) )
            {
                error_log("changeSmartLists had deleteSmartLists that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }

            foreach($deleteListArray as $listToDelete)
            {
                $deleteListArray = array();

                if(empty($listToDelete['listId']))
                {
                    // if we don't have a listId we'll just return an item that only
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

				if (TDOSmartList::userCanEditSmartList($listId, $session->getUserId(), $link) == false) {
                    $lastErrorCode = ERROR_CODE_ACCESS_DENIED;
                    $lastErrorDesc = ERROR_DESC_ACCESS_DENIED;
                    $deleteListArray['errorCode'] = $lastErrorCode;
                    $deleteListArray['errorDesc'] = $lastErrorDesc;
                    $deleteResults[] = $deleteListArray;
                    continue;
                }

                if(TDOSmartList::deleteSmartList($listId, $link) == false) {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;
                    $deleteListArray['errorCode'] = $lastErrorCode;
                    $deleteListArray['errorDesc'] = $lastErrorDesc;
                } else {
                    $userModifiedLists[] = $listId;
                }

                $deleteResults[] = $deleteListArray;
            }

            $resultsArray['deleted'] = $deleteResults;
        }

        $responseArray['results'] = $resultsArray;
        $actionsArray = array();

        $smartListHash = TDOSmartList::smartListHashForUser($session->getUserId(), $link);
        if($smartListHash != NULL)
            $responseArray['listHash'] = $smartListHash;

        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);

            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_LISTS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_LISTS;

            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the smart lists from the server failed with error: " . json_last_error() . " Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;

                error_log("changeSmartLists failed to commit transaction");
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

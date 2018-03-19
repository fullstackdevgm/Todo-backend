<?php
	
	include_once('TodoOnline/base_sdk.php');
    include_once('TodoOnline/syncmethodhandlers/SyncConstants.php');    
	include_once('TodoOnline/php/SessionHandler.php');	
    
	if(!$session->isLoggedIn())
	{
		error_log("HandleContextSyncMethods.php called without a valid session");
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
	
	$user = TDOUser::getUserForUserId($session->getUserId());

	if($user == false)
	{
		error_log("HandleContextSyncMethods.php unable to fetch logged in user: ".$session->getUserId());
        outputSyncError(ERROR_CODE_INVALID_SESSION, ERROR_DESC_INVALID_SESSION);
		return;
	}
    
    if($method == "syncContexts")
    {
        $link = TDOUtil::getDBLink();
        
        if(empty($link))
        {
            error_log("syncTasks failed to get DBLink");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            return;
        }
    

        if(!mysql_query("START TRANSACTION", $link))
        {
            error_log("syncContexts failed to start transaction");
            outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
            TDOUtil::closeDBLink($link);
            return;
        }
        
        
        $lastErrorCode = 0;
        $lastErrorDesc = NULL;        
        
        $userModifiedContexts = array();
        
        $responseArray = array();
        $resultsArray = array();
        
        // Lists are going to be posted in the variables: addContexts, updateContexts, and deleteContexts
        // The values will be a JSON encoded array of list properties like this:
        // $_POST['addContexts'] = "[{"tmpcontextid":"AFSDS2345", "name":"New List"}, {"tmpcontextid":"DF2345677", "name":"New List 2"}]"
        // $_POST['updateContexts'] = "[{"contextid":"AFSDS2345", "name":"New List"}, {"contextid":"DF2345677", "name":"New List 2"}]"
        // $_POST['deleteContexts'] = "[{"contextid":"AFSDS2345"}, {"contextid":"DF2345677"}]"

        // The response will be a single JSON response with arrays of results in keys: addResults, updateResults, and deleteResults like this:
        // [{"results":{"added":[{"tmpcontextid":"AFSDS2345", "contextid":"BDCF234234"}, ...], "updated":[...], "deleted":[...]},
        //  {"actions":{"update":[{"contextid":"AFSDS2345", "name":"task name"}, ...], "delete":[...]},
        //   "contexttimestamp":"234523423132"}]
 
        if(isset($_POST['addContexts']) == true)
        {
            $addResults = array();
            
            $addCtxArray = json_decode($_POST['addContexts'], true);

            if( ($addCtxArray === NULL) || empty($addCtxArray) )
            {
                error_log("syncContexts had addContexts that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncContexts failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }

            foreach($addCtxArray as $ctxToAdd)
            {
                $addedCtxArray = array();

                if(empty($ctxToAdd['tmpcontextid']))
                {
                    // if we don't have a tmpcontextid we'll just return an item that only
                    // has an error but at least there will be an error reported.
                    // I'm not sure how the client will match this up but it shouldn't
                    // ever happen.
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " tmpcontextid was missing.";
                    $addedCtxArray['errorcode'] = $lastErrorCode;
                    $addedCtxArray['errordesc'] = $lastErrorDesc;
                    $addResults[] = $addedCtxArray;
                    continue;
                }
                    
                $addedCtxArray['tmpcontextid'] = $ctxToAdd['tmpcontextid'];
                $tmpCtxId = $ctxToAdd['tmpcontextid'];
                
                if(empty($ctxToAdd['name']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " name was missing.";
                    $addedCtxArray['errorcode'] = $lastErrorCode;
                    $addedCtxArray['errordesc'] = $lastErrorDesc;
                }
                else
                {
                    $ctxName = $ctxToAdd['name'];
                    
                    
                    $newCtx = new TDOContext();
                    $newCtx->setName($ctxName);
                    $newCtx->setUserid($session->getUserId());
                    
                    if($newCtx->addContext($link))
                    {
                        $addedCtxArray['contextid'] = $newCtx->getContextid();
                        $userModifiedContexts[] = $newCtx->getContextid();
                    }
                    else
                    {
                        $lastErrorCode = ERROR_CODE_ERROR_ADDING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_ADDING_OBJECT;
                        $addedCtxArray['errorcode'] = $lastErrorCode;
                        $addedCtxArray['errordesc'] = $lastErrorDesc;
                    }
                }
                
                $addResults[] = $addedCtxArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncContexts failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['added'] = $addResults;
        }
        
        if(isset($_POST['updateContexts']) == true)
        {
            $updateResults = array();

            $contextArray = json_decode($_POST['updateContexts'], true);
            
            if( ($contextArray === NULL) || empty($contextArray) )
            {
                error_log("syncContexts had updateContexts that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncContexts failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($contextArray as $ctxToUpdate)
            {
                $updateCtxArray = array();
                
                if(empty($ctxToUpdate['contextid']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " contextid was missing.";;
                    $updateCtxArray['errorcode'] = $lastErrorCode;
                    $updateCtxArray['errordesc'] = $lastErrorDesc;
                    $updateResults[] = $updateCtxArray;
                    continue;
                }
                
                $ctxId = $ctxToUpdate['contextid'];
                $updateCtxArray['contextid'] = $ctxId;
                
                $ctx = TDOContext::getContextForContextid($ctxId, $link);
                if(empty($ctx))
                {
                    $lastErrorCode = ERROR_CODE_OBJECT_NOT_FOUND;
                    $lastErrorDesc = ERROR_DESC_OBJECT_NOT_FOUND;
                    $updateCtxArray['errorcode'] = $lastErrorCode;
                    $updateCtxArray['errordesc'] = $lastErrorDesc;
                    $updateResults[] = $updateCtxArray;
                    continue;
                }
                
                if(empty($ctxToUpdate['name']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " name was missing.";
                    $updateCtxArray['errorcode'] = $lastErrorCode;
                    $updateCtxArray['errordesc'] = $lastErrorDesc;
                }
                else
                {
                    $ctxName = $ctxToUpdate['name'];
                    
                    $ctx->setName($ctxName);
                    
                    if($ctx->updateContext($link) == false)
                    {
                        $lastErrorCode = ERROR_CODE_ERROR_UPDATING_OBJECT;
                        $lastErrorDesc = ERROR_DESC_ERROR_UPDATING_OBJECT;
                        $updateCtxArray['errorcode'] = $lastErrorCode;
                        $updateCtxArray['errordesc'] = $lastErrorDesc;
                    }
                    else
                    {
                        $userModifiedContexts[] = $ctx->getContextid();
                    }
                }
                
                $updateResults[] = $updateCtxArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncContexts failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['updated'] = $updateResults;
        }

        if(isset($_POST['deleteContexts']) == true)
        {
            $deleteResults = array();
            
            $deleteContexts = json_decode($_POST['deleteContexts'], true);
            
            if( ($deleteContexts === NULL) || empty($deleteContexts) )
            {
                error_log("syncContexts had deleteContexts that could not be parsed for user: " . TDOUser::usernameForUserId($session->getUserId()));
                outputSyncError(ERROR_CODE_ERROR_PARSING_DATA, ERROR_DESC_ERROR_PARSING_DATA);
                mysql_query("ROLLBACK", $link);
                TDOUtil::closeDBLink($link);
                return;
            }
            
//            if(!mysql_query("START TRANSACTION", $link))
//            {
//                error_log("syncContexts failed to start transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            foreach($deleteContexts as $ctxToDelete)
            {
                $deleteCtxArray = array();
                
                if(empty($ctxToDelete['contextid']))
                {
                    $lastErrorCode = ERROR_CODE_MISSING_REQUIRED_PARAMETERS;
                    $lastErrorDesc = ERROR_DESC_MISSING_REQUIRED_PARAMETERS . " contextid was missing.";
                    $deleteCtxArray['errorcode'] = $lastErrorCode;
                    $deleteCtxArray['errordesc'] = $lastErrorDesc;
                    $deleteResults[] = $deleteCtxArray;
                    continue;
                }
                
                $ctxId = $ctxToDelete['contextid'];
                $deleteCtxArray['contextid'] = $ctxId;
                
                if(TDOContext::deleteContext($ctxId, $link) == false)
                {
                    $lastErrorCode = ERROR_CODE_ERROR_DELETING_OBJECT;
                    $lastErrorDesc = ERROR_DESC_ERROR_DELETING_OBJECT;
                    $deleteCtxArray['errorcode'] = $lastErrorCode;
                    $deleteCtxArray['errordesc'] = $lastErrorDesc;
                }
                else
                {
                    $userModifiedContexts[] = $ctxId;
                }
                
                $deleteResults[] = $deleteCtxArray;
            }
            
//            if(!mysql_query("COMMIT", $link))
//            {
//                error_log("syncContexts failed to commit transaction");
//                outputSyncError(ERROR_CODE_DB_LINK_FAILED, ERROR_DESC_DB_LINK_FAILED);
//                mysql_query("ROLLBACK", $link);
//                TDOUtil::closeDBLink($link);
//                return;
//            }
            
            $resultsArray['deleted'] = $deleteResults;
        }
        
        $responseArray['results'] = $resultsArray;

        
        $actionsArray = array();
        
        if(isset($_POST['contexttimestamp']))
            $timestamp = $_POST['contexttimestamp'];
        else
            $timestamp = 0;
        
        $contextsArray = array();
        
        $contexts = TDOContext::getContextsForUserModifiedSince($session->getUserId(), $timestamp, false, $link); // get all non deleted contexts
        if(isset($contexts))
        {
            foreach($contexts as $context)
            {
                if(in_array($context->getContextid(), $userModifiedContexts) == false)
                {
                    $contextProperties = $context->getPropertiesArray();
                    $contextsArray[] = $contextProperties;
                }
            }
        }
        
        $actionsArray['update'] = $contextsArray;
        
        // only get deleted tasks if they have synced before with us
        if($timestamp != 0)
        {
            $contexts = TDOContext::getContextsForUserModifiedSince($session->getUserId(), $timestamp, true, $link); // get all deleted contexts since timestamp
            if(isset($contexts))
            {
                $contextsArray = array();

                foreach($contexts as $context)
                {
                    if(in_array($context->getContextid(), $userModifiedContexts) == false)
                    {
                        $contextProperties = $context->getPropertiesArray();
                        $contextsArray[] = $contextProperties;
                    }
                }
            }
            $actionsArray['delete'] = $contextsArray;
        }
        
        $responseArray['actions'] = $actionsArray;
        
        $contextTimeStamp = TDOContext::getContextTimestampForUser($session->getUserId(), $link);
        if($contextTimeStamp != NULL)
            $responseArray['contexttimestamp'] = $contextTimeStamp;


        $jsonResponse = json_encode($responseArray);
        if(json_last_error() != JSON_ERROR_NONE)
        {
            mysql_query("ROLLBACK", $link);
            
            $lastErrorCode = ERROR_CODE_ERROR_INVALID_UTF8_IN_CONTEXTS;
            $lastErrorDesc = ERROR_DESC_ERROR_INVALID_UTF8_IN_CONTEXTS;
            
            outputSyncError($lastErrorCode, $lastErrorDesc);
            error_log("json_encoding the contexts from the server failed with error: " . json_last_error() . " Reporting ".$lastErrorCode." error desc ".$lastErrorDesc." For user: " . $session->getUserId());
        }
        else
        {
            if(!mysql_query("COMMIT", $link))
            {
                $lastErrorCode = ERROR_CODE_DB_LINK_FAILED;
                $lastErrorDesc = ERROR_DESC_DB_LINK_FAILED;
                
                error_log("syncTasks failed to commit transaction");
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

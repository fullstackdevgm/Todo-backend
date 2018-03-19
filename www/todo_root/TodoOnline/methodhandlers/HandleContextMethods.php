<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	
	if(isset($_SERVER['HTTP_REFERER']))
	{
		$referrer = $_SERVER['HTTP_REFERER'];
	}
	else
	{
		$referrer = ".";
	}	
	
	if(!$session->isLoggedIn())
	{
		error_log("Method called without a valid session");
		
		echo '{"success":false}';
		return;
	}
	
	if($method == "addContext")
	{
		if(!isset($_POST['contextName']))
		{
			error_log("Method addContext missing parameter: contextName");
			echo '{"success":false}';
			return;
		}
		
		$TDOContext = new TDOContext();
		$TDOContext->setName(htmlspecialchars($_POST['contextName']));
		$TDOContext->setUserid($session->getUserId());
		
		if($TDOContext->addContext())
		{
//			TDOChangeLog::addChangeLog($TDOContext->getContextid(), $session->getUserId(), $TDOContext->getContextid(), $TDOContext->getName(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
//			$taskHTML = contentDisplayForTask($TDOTask);
			//header("Location:".$referrer);
			echo '{"success":true, "contextname":"'.htmlspecialchars($TDOContext->getName()).'", "contextid":"'.$TDOContext->getContextid().'"}';
		}
		else
		{
            echo json_encode(array(
                'success' => FALSE,
                'error' => _('Creation of context failed'),
            ));
		}	
	}
	elseif($method == "deleteContext")
	{
		if(!isset($_POST['contextId']))
		{
			error_log("Method deleteContext missing parameter: contextId");
			echo '{"success":false}';
			return;
		}
		
		$context = TDOContext::getContextForContextid($_POST['contextId']);
		if(empty($context))
		{
			error_log("Method deleteContext unable to load context: ".$_POST['contextId']);
			echo '{"success":false}';
			return;
		}
		
		if(TDOContext::deleteContext($context->getContextid()))
		{
//			TDOChangeLog::addChangeLog($task->getListid(), $session->getUserId(), $task->getId(), $task->getSummary(), ITEM_TYPE_TASK, CHANGE_TYPE_DELETE, CHANGE_LOCATION_WEB);
			echo '{"success":true}';
		}
		else
		{
			error_log("Method deleteContext failed to delete context: ".$context->getContextid());	
			echo '{"success":false}';
		}	
	}
	elseif($method == "updateContext")
	{
		$haveUpdatedValues = false;
		$jsonValues = array();
		
		if(!isset($_POST['contextId']))
		{
			error_log("Method updateContext missing parameter: contextId");
			echo '{"success":false}';
			return;
		}
		
		$context = TDOContext::getContextForContextid($_POST['contextId']);
		if(empty($context))
		{
			error_log("Method updateContext unable to load task: ".$_POST['contextId']);
			echo '{"success":false}';
			return;
		}

		if(isset($_POST['contextName']))
		{
			$jsonValues['old-contextName'] = (string)$context->getName();

			$context->setName(htmlspecialchars($_POST['contextName']));

			$jsonValues['contextName'] = (string)$context->getName();
			$haveUpdatedValues = true;
		}

		if(!$haveUpdatedValues)
		{
			error_log("Method updateContext was called with no values to update");
			echo '{"success":false}';
			return;
		}
		
		if($context->updateContext())
		{
			echo '{"success":true, "contextName":"'.htmlspecialchars($_POST['contextName']).'"}';
			//TDOChangeLog::addChangeLog($task->getListid(), $session->getUserId(), $task->getId(), $task->getSummary(), ITEM_TYPE_TASK, CHANGE_TYPE_MODIFY, CHANGE_LOCATION_WEB, NULL, NULL, $jsonChangedValues);
		}
		else
		{
			error_log("Method updateContext failed to update context: ".$_POST['contextId']);	
			echo '{"success":false}';
		}
    }
    elseif($method == "assignContext")
	{

		if(!isset($_POST['taskId']))
		{
			error_log("Method assignContext missing parameter: taskId");
			echo '{"success":false}';
			return;
		}
		
		if(isset($_POST['contextId']))
		{
			$contextId = $_POST['contextId'];
			$context = TDOContext::getContextForContextid($contextId);
		}
		else
		{
			$contextId = null;
			$context = null;
		}
		
		if(!empty($context))
		{
			if($context->getUserid() != $session->getUserId())
			{
				error_log("Method assignContext invalid context for user: ".$_POST['contextId']);
				echo '{"success":false}';
				return;
			}
		}
		
				
		$task = TDOTask::getTaskFortaskId($_POST['taskId']);
		if(empty($task))
		{
			error_log("Method assignContext unable to load task: ".$_POST['taskId']);
			echo '{"success":false}';
			return;
		}

		$listid = $task->listId();
		if(empty($listid))
		{
			error_log("Method assignContext unable to find list for task: ".$_POST['taskId']);
			echo '{"success":false}';
			return;
		}
		
		if(TDOList::userCanEditList($listid, $session->getUserId()) == false)
		{
			error_log("Method assignContext found that user cannot edit the list: ".$listid);
			echo '{"success":false}';
			return;
		}
		
		if(TDOContext::assignTaskToContext($_POST['taskId'], $contextId, $session->getUserId()))
		{
            // flag the task as being updated
            TDOTask::updateTimestampForTask($task->taskId());
            
			if($context)
			{
				$contextName = $context->getName();
				$contextId = $context->getContextid();
			}
			else
			{
				$contextName = _('No Context');
				$contextId = 0;	
			}
				
			echo '{"success":true, "contextName":"'.$contextName.'", "contextId":"'.$contextId.'"}';
		}	
		else
		{
			error_log("Method assignContext failed to assign context: ".$_POST['contextId']);	
			echo '{"success":false}';
		}
    }

?>
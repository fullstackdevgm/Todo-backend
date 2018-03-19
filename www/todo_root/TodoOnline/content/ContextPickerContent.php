<?php

	$contexts = TDOContext::getContextsForUser($session->getUserId());
	
	$noContextSelectedString = '';
	
	if($task->getContextid() == null)
		$noContextSelectedString = ' checked';	
	
	$taskHTML .= '	<span class="label" >
								<input type="radio" onclick="updateContextForTask(\''.$taskid.'\',\'\' )" name="context_selected_'.$taskid.'" id="context_option_'.$taskid.'_noContext"'.	$noContextSelectedString.'/>
								<label for="context_option_'.$taskid.'_noContext">No Context</label>
							</span>';	

	foreach($contexts as $context)
	{
		$contextId = $context->getContextid();
		
		if ($task->getContextid() == $contextId)
			$selectedString = ' checked';
		else
			$selectedString = '';
						
		$taskHTML .= '	<span class="label" >
							<input type="radio" onclick="updateContextForTask(\''.$taskid.'\',\''.$contextId.'\' )" name="context_selected_'.$taskid.'" id="context_option_'.$taskid.'_'.$contextId.'"'.	$selectedString.'/>
							<label for="context_option_'.$taskid.'_'.$contextId.'">'.$context->getName().'</label>
						</span>';
	}



?>
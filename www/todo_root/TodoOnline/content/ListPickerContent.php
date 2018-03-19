<?php

	$lists = TDOList::getListsForUser($session->getUserId());
	
	foreach($lists as $list)
	{
	
		$listId = $list->listId();
		
		if ($task->listId() == $listId)
			$selectedString = ' checked';
		else
			$selectedString = '';	
				
		$taskHTML .= '<span class="label" >
						<input type="radio" onclick="updateListForTask(\''.$taskid.'\',\''.$listId.'\' )"name="list_selected_'.$taskid.'" id="list_option_'.$taskid.'_'.$listId.'"'.$selectedString.'/>
						<label for="list_option_'.$taskid.'_'.$listId.'">'.$list->name().'</label>
					</span>';
	}
	
	
?>
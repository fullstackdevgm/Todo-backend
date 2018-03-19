<div class="button_toolbar">
<?php
	/*
	Description: ContentToolbar.php creates the button toolbar inside of any *Content.php file
	Parameters:
				$toolbarButtons: array of PBButtons. 
				
	Note: The buttons are drawn left to right starting from $toolbarButtons[0] to $toolbarButtons[n]					
	*/
	
	if(isset($toolbarButtons))
	{
		foreach($toolbarButtons as $button)
		{
			$button->drawButton();
		}
	}
?>
</div>
<h1><?php _e('Mobile Setup'); ?></h1>

<?php
	//Creating Toolbar buttons:
	//	Add PBButtons to $toolbarButtons array
	//
	//	$newButton = new PBButton;
	//	$newButton->setLabel('new button');
	//	$newButton->setUrl('someUrl');
	//	$otherButton = newPBButton;
	
	//  $toolbarButtons = array($newButton, $otherButton);
	//
	// NOTE: If no buttons are added, then the toolbar buttons will not be drawn 
	
	$goBack = new PBButton;
	$goBack->setLabel('< Dashboard');
	$goBack->setUrl('.');
	
	$toolbarButtons = array($goBack);
	include_once('TodoOnline/content/ContentToolbarButtons.php');

	
	if($session->isLoggedIn())
	{
		$userId = $session->getUserId();
		
		echo "<br/>";

		$user = TDOUser::getUserForUserId($userId);
		
		if(!empty($user))
		{
			$formPassword = "••••••";
			$formUsername = $user->username();
			$aPassword = $user->password();

			if(empty($formUsername) || empty($aPassword) )
			{
				echo '<h3>' . _('User account not configured') . '</h3>';
                _e('It appears your user account is not set up for Caldav access.');
				echo '<br><br>';
                printf(_('Visit the %sAccount Settings%s page to set a User Name and Password for Caldav access.'), '<a href="?settings=unicorns_and_lollypops">', '</a>');
			}
			else
			{
                echo "<h3>" . _('Caldav Instructions') . "</h3>";
				echo "<br>" . _('Enter the following information in your Caldav setup:') . "<br>";
				echo "<table cellspacing=\"10\">";
				echo "<tr><td>" . _('Server:') . "</td><td></td><td>cal.plunkboard.com</td></tr>";
				echo "<tr><td>" . _('User Name:') . "</td><td></td><td id='username'>".$formUsername."</td></tr>";
				echo "<tr><td>" . _('Password:') . "</td><td></td><td id='password'>".$formPassword."</td></tr>";
				echo "<tr><td>" . _('Description:') . "</td><td></td><td>Plunkboard</td></tr>";
				echo "</table>";
			}
		}
		
		echo "<br><br>";
	} 
	
?>

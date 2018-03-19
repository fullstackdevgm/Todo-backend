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
	?>

<h1>Notes</h1>

<?php
	
	if($session->isLoggedIn())
	{
//		if(isset($_GET['list']))
//		{
//            $listid = $_GET['list'];		
//			
//			$events = PBEvent::getEventsForList($listid);
//			
//			if($events)
//			{
//				echo "<table cellpadding=\"10\">";
//				
//				foreach($events as $event)
//				{
//					echo "<tr>";
//					$eventid = $event->getId();
//					
//					echo "<td>$eventid</td>";
//					
//					$summary = $event->getSummary();
//					echo "<td>$summary</td>";
//					
//					echo "</tr>";
//				}
//				
//				echo "</table>";
//				
//			}
//			else
//				echo "No tasks";
//		}
//		else
//			echo "list id was not found";
	} 

	echo _("Notes are not yet supported");
	
	?>

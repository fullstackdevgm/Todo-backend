<?php

	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');

    //We should never see this from inside the Facebook Canvas
    $params = array("scope"=>"email");
    $fb_login_url = $facebook->getLoginUrl($params);

    //echo '<u><div onclick="top.location=\''.$fb_login_url.'\'">Log in via Facebook</div></u><br/>';

    //NCB - Taking out Facebook integration for initial release. When we plug it in again, change 'value' to true
    echo '<input type="hidden" id="showFacebookLink" value="false" link="'.$fb_login_url.'" />';
    
	//Don't show the create account link unless the user has an invitation
	if(TDOSession::savedURLHasInvitation())
	{		echo '<input type="hidden" id="showCreateAccountLink" value="true" />';


	}
	else
	{
		//echo "Todo Cloud is currently joined by invitation only.";
//      echo '<br/><br/><span style="color:lightgray;">[Placeholder for Todo Cloud preview content]</span>';
	}

?>


<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');	
	
if(isset($_SESSION['ref']))
    $referrer = $_SESSION['ref'];
else
    $referrer = ".";

$userid = $session->getUserId();
if($method == "linkFacebook")
{

    # Let's see if we have an active session 
    $fbuserid = $facebook->getUser();

    if($fbuserid)
    { 
        try
        {
            $userData = $facebook->api('/me', 'GET');
        }
        catch (FacebookApiException $e)
        {
            error_log("HandleLinkFacebook FacebookApiException ");
        }
        
        if(!empty($userData))
        {        
            if(TDOUser::existsFacebookUser($fbuserid) == false)
            {
                if(TDOUser::linkUserToFacebookId($userid, $fbuserid) == false)
                {
                    echo "<script type=\"text/javascript\">";
                    echo "alert('Failed to link account')";
                    echo "</script>";  
                }
                
            }
            else
            {
                echo "<script type=\"text/javascript\">";
                echo "alert('Your Facebook account is already tied to a Todo Cloud account')";
                echo "</script>";            
            }
        }
    }
    else
    {
        $params = array("scope"=>"email");
        $login_url = $facebook->getLoginUrl();  
        echo "<script type=\"text/javascript\">";
        echo "top.location=\"$login_url\"";
        echo "</script>";
        return;
    }
    
    echo "<script type=\"text/javascript\">";
    echo "top.location=\"$referrer\"";
    echo "</script>";

}
elseif($method == "unlinkFacebook")
{
    if(TDOUser::unlinkFacebookAccountForUser($userid))
        echo '{"success":true}';
    else
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Unable to unlink account'),
        ));
}
?>
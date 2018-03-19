<?php
	
	if(isset($_SESSION['ref']))
	{
		$referrer = $_SESSION['ref'];
	}
	else
	{
		$referrer = ".";
	}
	
	$retVal = 0;
	
	if( isset($_POST['username']) && isset($_POST['password']) ) 
	{
		$response = array();

        $retVal = $session->login(trim($_POST['username']), trim($_POST['password']));
		
		if(!empty($retVal['error']))
        {
            $error = $retVal['error'];
//            if($error['id'] == 0)
//            {
//            	$response['success'] = false;
//            	$response['upgrading'] = true;
//            	$response['error'] = 'Your user is being upgraded with awesomeness. Please wait...';
//            	 
//                //echo "Your user is being upgraded with awesomeness.  Try login again in a minute.";
//                
//            }
			if ($error['id'] == 1)
			{
				$response['success'] = false;
				$response['maintenance'] = true;
				$response['error'] = _('Your account is currently under maintenance. Please wait...');
			}
            else
            {
            	$response['success'] = false;
            	$response['error'] = _('Invalid username or password');
            }
        }
        else
        {
            //Login was successful
            //header("Location:".$referrer);
            
            
            /*
if(!empty($retVal['subscription_time_added']))
            {
	            $response['migrated'] = true;
	            $response['timeadded'] = $retVal['subscription_time_added'];
            }
*/	
            
            $response['success'] = true;
        }
        
        echo json_encode($response);

	}
    elseif(isset($_POST['facebook']))
    {
        if($session->setupFacebookSession($facebook))
        {
            echo '{"success":true}';
        }
        else
        {
            $fbId = $facebook->getUser();
            if(!$fbId)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('oauth'),
                ));
            }
            else
            {
                //TODO: Check for valid invitation before creating account
                 $result = $session->createFacebookUser($facebook);
                 if($result > 0)
                 {
                    echo '{"success":true}';
                 }
                 elseif($result == false)
                 {
                     echo json_encode(array(
                         'success' => FALSE,
                         'error' => _('oauth'),
                     ));
                 }
                 else
                 {
                    $message = _("Facebook login was unsuccessful");
                    if($result == NO_EMAIL_ERROR)
                    {
                        $message = _("Unable to retrieve email address from Facebook");
                    }
                    elseif($result == EMAIL_TAKEN_ERROR)
                    {
                        $message = sprintf(_("Your email address has already been registered with Todo Cloud. If you already have a Todo Cloud account, you may link it to this Facebook account in Settings->Account at %s"), 'www.' . SITE_BASE_URL);
                    }
                    elseif($result == EMAIL_TOO_LONG_ERROR)
                    {
                        $message = _("Your email address is too long to be used as a username");
                    }
                    elseif($result == FB_USER_EXISTS_ERROR)
                    {
                        error_log("HandleLogin.php trying to create a user account for an existing Facebook account");
                    }
                     echo json_encode(array(
                         'success' => FALSE,
                         'error' => $message,
                     ));
                 }

            }
        
        }
    }
    	
?>

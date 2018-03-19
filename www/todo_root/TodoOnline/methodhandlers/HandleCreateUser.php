<?php

	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');

	if(isset($_SESSION['ref']))
	{
		$referrer = $_SESSION['ref'];
	}
	else
	{
		$referrer = ".";
	}

    //While in invite only mode, don't create a user unless they're acting
    //on a valid invitation
//    if(TDOSession::savedURLHasInvitation())
//    {
        if(isset($_POST['username']) || isset($_POST['password']))
        {
            $username = trim($_POST['username']);

            if ($session && $session->getUserId()) {
                $user = TDOUser::getUserForUserId($session->getUserId());
                if ($user->username() === $username) {
                    if (isset($_POST['numOfSubscriptions']) && isset($_POST['stripeToken'])) {
                        $method = 'createTeamAccountWithTrial';
                        include_once('TodoOnline/methodhandlers/HandleTeamMethods.php');
                        exit();
                    }
                }
            }
            $user = new TDOUser();

            if(strlen($username) > USER_NAME_LENGTH)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The username you have entered is too long. Please enter a shorter username.'),
                ));
                return;
            }
            
            if(TDOUser::existsUsername($username))
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('Email address is already in use.'),
                ));
                return;
            }
            
            $user->setUsername($username);
            
            $password = trim($_POST['password']);
            if(strlen($password) > PASSWORD_LENGTH)
            {
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The password you have entered is too long. Please enter a shorter password.'),
                ));
                return;
            }
			else if(strlen($password) < PASSWORD_MIN_LENGTH)
			{
                echo json_encode(array(
                    'success' => FALSE,
                    'error' => _('The password you have entered is too short. Please enter a password with a length of at least six characters.'),
                ));
				return;
			}
            $user->setPassword($password);

            // CRG - removing legacy support
            // check to see if there is a Todo Online account
//            $tdoLegacy = new TDOLegacy();
//            
//            $legacyres = $tdoLegacy->authUser($username, $password);
//            if(empty($legacyres['error']))
//            {
//                if(!empty($legacyres['user']))
//                {
//                    echo '{"success":false, "error":"Username already in use"}';
//                    return;
//                }
//            }
            
            if(isset($_POST['firstname']))
                $user->setFirstName(trim($_POST['firstname']));
            if(isset($_POST['lastname']))
                $user->setLastName(trim($_POST['lastname']));

            if(isset($_POST['emailoptin']))
            {
                if($_POST['emailoptin'] == "0")
                    $user->setEmailOptOut(1);
            }
            if (isset($_COOKIE['interface_language']) && $_COOKIE['interface_language'] !== '') {
                $user->setLocale($_COOKIE['interface_language']);
            } else {
                $user->setLocale(TDOInternalization::getUserPreferredLocale());
            }
            $user->setBestMatchLocale(TDOInternalization::getUserBestMatchLocale($user->locale()));
//            $user->setBestMatchLocale($_POST['locale-selected']);

            if($user->addUser())
            {
                $session->login($username, $password);
                //		$referrer = ".";
                if (isset($_POST['numOfSubscriptions']) && isset($_POST['stripeToken'])) {
                    $method = 'createTeamAccountWithTrial';
                    include_once('TodoOnline/methodhandlers/HandleTeamMethods.php');
                    exit();
                }
            } else {
                $response = array(
                    'success' => FALSE,
                    'error' => _('Unable to create new user. Please try again later.'),
                );
                echo json_encode($response);
                die;
            }

        }

        $response['success'] = true;
       // header("Location:".$referrer);
//    }
//    else
//    	$response['success'] = false;


    echo json_encode($response);
      // echo "Todo Cloud is currently in development. You must have an invitation to try it out<br>";


?>

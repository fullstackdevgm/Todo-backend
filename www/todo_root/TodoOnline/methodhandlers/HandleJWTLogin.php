<?php

	$retVal = 0;

	if( isset($_POST['username']) && isset($_POST['password']) )
	{
		$response = array();

		$retVal = TDOAuthJWT::login(trim($_POST['username']), trim($_POST['password']));
		if(!empty($retVal['error'])) {
			$error = $retVal['error'];
			if ($error['id'] == 1) {
				$response['success'] = false;
				$response['maintenance'] = true;
				$response['error'] = _('Your account is currently under maintenance. Please wait...');
			} else {
				$response['success'] = false;
				$response['error'] = _('Invalid username or password');
			}
		}
		else
		{
				//Login was successful
				//header("Location:".$referrer);

				// If this is an admin session, fail the login if the user is NOT an
				// admin.
				if (!empty($_SESSION['ADMIN']) && $_SESSION['ADMIN'] == TRUE) {
					$userid = $retVal['userid'];
					$adminLevel = TDOUser::adminLevel($userid);
					if ($adminLevel == ADMIN_LEVEL_NONE) {
						$response['success'] = false;
						$response['error'] = _('Invalid username or password');
						echo json_encode($response);
						exit();
					}
	      }


				$response['token'] = $retVal['token'];
				$response['success'] = true;
		}

		echo json_encode($response);
	}

?>

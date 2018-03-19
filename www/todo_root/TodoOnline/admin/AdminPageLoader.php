<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
        <head>
        	<title>Todo Cloud - Admin</title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_BASE; ?>" />
        	<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_STYLE; ?>" />
        	<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" />
        	<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_ADMIN_STYLE; ?>" />
        	<link rel="shortcut icon" href="<?php echo TP_IMG_PATH_FAV_ICON; ?>" type="image/x-icon" />
        	<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>" ></script>
        	<script type="text/javascript" src="<?php echo TP_JS_PATH_ADMIN_UTILS; ?>"></script>
        	<script type="text/javascript" src="<?php echo TP_JS_PATH_LANG; ?>"></script>
        	<style> html{overflow: auto}body{background: white}</style>
        </head>
        <body>
       		<div id="modal_overlay">
        		<div id="modal_overlay_message"></div>
        	</div>
        	<div id="modal_container">
	        	<div id="modal_header">
		        	<div id="modal_header_content"></div>
	        	</div>
	        	<div id="modal_body"></div>
	        	<div id="modal_err"></div>
	        	<div id="modal_footer"></div>
        	</div>

        	<div id="click_to_dismiss_overlay"></div>

        	<div id="messageContainer"></div>

            <?php
				$adminLevel = TDOUser::adminLevel($session->getUserId());
				if( ($session->isLoggedIn()) && ($adminLevel > ADMIN_LEVEL_NONE) )
				{
                    $loadContent = true;
                }
                else
                {
					$session->logout();
                }
            ?>

			<div id="main_container">

<div id="HEADER">
<a href="." style="text-decoration:none;">
<div id="LOGO"></div>
</a>


</div>

<?php echo "<input id='user_admin_level' value='$adminLevel' type='hidden'/>"; ?>

				<div id="controls">
                    <?php
                        if(isset($loadContent))
						{
							include_once('TodoOnline/admin/AdminControls.php');
                        }
						else
                            //print 'doh! no user id';
                    ?>
                </div>

                <div id="content">
                    <?php
                    	if(isset($loadContent))
						{
							if(isset($_GET['section']))
							{
								switch($_GET['section'])
								{
									case "systemstats":
										include_once('TodoOnline/admin/AdminSystemStatsContent.php');
										exit();
										break;
									case "system":
										include_once('TodoOnline/admin/AdminSystemContent.php');
										exit();
										break;
                                    case "systemnotification":
                                        include_once('TodoOnline/admin/AdminSystemNotificationContent.php');
                                        exit();
                                        break;
									case "users":
										include_once('TodoOnline/admin/AdminUsersContent.php');
										exit();
										break;
									case "teams":
										include_once('TodoOnline/admin/AdminTeamsContent.php');
										exit();
										break;
									case "promocodes":
										include_once('AdminPromoCodeContent.php');
										exit();
										break;
                                    case "giftcodes":
                                        include_once('AdminGiftCodeContent.php');
                                        exit();
                                        break;
                                    case "referrals":
                                        include_once('AdminReferralsContent.php');
                                        exit();
                                        break;
									case "systemsettings":
										include_once('AdminSystemSettingsContent.php');
										exit();
										break;
//                                    case "messagecenter":
//                                        include_once('AdminMessageCenterContent.php');
//                                        exit();
//                                        break;
								}
							}

							// nothing above was taken so load the default content
							include_once('TodoOnline/admin/AdminDashboardContent.php');
						}
						else
                        {
                            TDOSession::saveCurrentURL();
							print "Login to access the admin console";
//							if(isset($_GET['signup']))
//								include_once('TodoOnline/content/SignupContent.php');
//							else
//								include_once('TodoOnline/content/LoginContent.php');
                        }
                    ?>
                    <br/><br/>
                    <div id="login_form" class="settings_container" style="position:static;">
<?php
    include_once('TodoOnline/ajax_config.html');

	if(isset($loadContent))
	{
		echo '<a href="?settings=green_eggs_and_ham">Settings</a>';
		if($session->isLoggedIn() && !$session->isFB())
		{
			echo '<span> - </span>';
			echo '<a href="?method=logout">Logout</a>';
		}
	}
	else
	{
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
			$retVal = $session->login($_POST['username'], $_POST['password']);
            if(!empty($retVal['error']))
            {
                $error = $retVal['error'];
                echo "Invalid username or password<br>";
			}
			else
			{
				//Login was successful
				header("Location:".$referrer);
			}
		}

		$urlSymbol = "&";
		if(strpos($referrer, "?") == false)
		{
			$urlSymbol = "?";
		}

        //NCB - Taking out Facebook integration for initial release.
//		$fbLoginLink = "<a href=".$referrer.$urlSymbol."fblogin=jimminycricket>Login via Facebook</a>";

		//login form
		echo '<script>loadAdminSignInForm();</script>';
		
/*
		echo '<form  name="loginForm" action="." method="POST">
		<table  class="login_form" cellpadding="0" cellspacing="0">
		<tr>
		<td>Login Email</td>
		<td>Password</td>
		<td></td>
		</tr>
		<tr>
		<td><input type="text" name="username" id="loginUsername" autofocus="autofocus"></td>
		<td><input type="hidden" name="method" value="login">
		<input type="password" name="password"></td>
		<td><input type="submit" name="loginButton" value="Log In"></td>
		</tr>
		<tr>
		<td>'.$fbLoginLink.'</td>
		<td></td>
		<td></td>
		</tr>
		</table>
		</form>';
*/

	}
	?>
</div>
                </div>

			</div>
        </body>
</html>


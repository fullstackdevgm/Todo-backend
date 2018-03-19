<?php

	// It appears that some clients are not sending the user agent.
	// Test to see if it exists before trying to use it.
    $isIOS = false;
	if (!empty($_SERVER['HTTP_USER_AGENT']))
	{
		if ( (strstr($_SERVER['HTTP_USER_AGENT'], 'iPhone'))
			|| (strstr($_SERVER['HTTP_USER_AGENT'], 'iPod'))
			|| (strstr($_SERVER['HTTP_USER_AGENT'], 'iPad'))
			)
		{
			$isIOS = true;

            if(isset($_GET['showtask']))
            {
                header("Location:appigotodo://todo-cloud.com/showtask?taskid=".$_GET['showtask']);
                exit();
            }
            if(isset($_GET['appSettings']) && isset($_GET['option']))
            {
                if($_GET['option'] == "notifications")
                {
                    header("Location:appigotodo://todo-cloud.com/notificationsettings");
                    exit();
                }
            }
		}
	}

    //Cookies must be set before sending any html output, so we must set cookies for showTask now
    $showTaskHtml = NULL;
    if(isset($_GET['showtask']))
    {
        $showTaskHtml = setCookiesForShowTask($_GET['showtask'], $session);
    }
    if(isset($_GET['showlist']))
    {
        $showTaskHtml = setCookiesForShowList($_GET['showlist'], $session);
    }
    if (isset($_COOKIE['hide_coach']) && $_COOKIE['hide_coach'] === 'ready') {
        setcookie('hide_coach', 'true', strtotime('+1 year'), '/');
    }

?><!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
        <head>
        	<title id="page_title"><?php _e('Todo Cloud - To-do lists simple enough for you, your friends, and your life'); ?></title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
			<meta name="description" content="<?php _e('Get things done on your own or as a group and access your tasks, projects, and checklists on all your devices.'); ?>" />
			<meta name="keywords" content="<?php _e('todo, tasks, reminders, projects, to-do list, checklist, collaboration, teams, teamwork, groups, ios, iphone, ipad, ipod touch, mac, android'); ?>" />
			<meta name="robots" content="noodp,noydir" />
			<meta name="apple-itunes-app" content="app-id=568428364" />
			<meta name="application-name" content="Todo Cloud" />


<?php
	if ($isIOS == true)
	{
		echo '<meta name="apple-itunes-app" content="app-id=568428364, affiliate-data=tRWoe/s2O/A" />';
	}
?>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
            <script>
                if (!window.jQuery) {
                    document.write('<script src="/js/jquery.1.11.0.min.js"><\/script>');
                }
            </script>

			<script> window.$j = jQuery.noConflict(); </script>
			<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
            <link href='//fonts.googleapis.com/css?family=Roboto:500,400,300,100' rel='stylesheet' type='text/css'>

			<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_BASE; ?>" />
        	<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_STYLE; ?>" />
        	<link rel="stylesheet" type="text/css" href="<?php echo TP_CSS_PATH_APP_SETTINGS; ?>" />
        	<link rel="stylesheet" type="text/css" media="print" href="<?php echo TP_CSS_PATH_PRINT_STYLE; ?>" />
            <?php if ((!$session->isLoggedIn() && !isset($_GET['referralunsubscribe'])) || isset($_GET['todo-for-business'])) : ?>
            <link rel="stylesheet" type="text/css" href="/css/landing-page.css" />
            <?php endif; ?>
			<link rel="shortcut icon" href="<?php echo TP_IMG_PATH_FAV_ICON; ?>" type="image/x-icon" />
            <link rel="canonical" href="http://www.appigo.com/todo-cloud" />
			<script type="text/javascript" src="<?php echo TP_JS_PATH_LANG; ?>"></script>
			<script type="text/javascript" src="/js/sprintf.min.js" ></script>
			<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>" ></script>
			<script type="text/javascript" src="<?php echo TP_JS_PATH_VERIFY_EMAIL_FUNCTIONS; ?>" ></script>
			<script type="text/javascript" src="/js/messenger.min.js" ></script>
            <link rel="stylesheet" type="text/css" href="/css/messenger.css" />
            <link rel="stylesheet" type="text/css" href="/css/messenger-theme-air.css" />

<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
 (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
 m=s.getElementsByTagName(o)[0];
 a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
 })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
<?php
$userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
$gaOptOut = TRUE;
if (!empty($userSettings)) {
    $gaOptOut = $userSettings->googleAnalyticsTracking();
}
    ?>
<?php
	// New Google Analytics code to set User ID.
	// $userId is a unique, persistent, and non-personally identifiable string ID.
	if (isset($userId)) {
  $gacode = "ga('create', 'UA-21351784-13', { 'userId': '%s' });";
  echo sprintf($gacode, $userId);
}?>

<?php
	if($session->isLoggedIn() && $gaOptOut)
	{
?>
ga('create', 'UA-21351784-13', { 'userId': '<?php echo $session->getUserId(); ?>' });
<?php
	}
	else
	{
?>
ga('create', 'UA-21351784-13');
<?php
	}
?>

ga('send', 'pageview');
<?php
	if (!empty($userSettings))
	{
        $gaOptOut = $userSettings->googleAnalyticsTracking();
?>
var userSettings = {
    showOverdueTasks: <?php echo intval($userSettings->showOverdueSection()); ?>,
    focusShowUndueTasks: <?php echo intval($userSettings->focusShowUndueTasks()); ?>
};
<?php
	}
?>
</script>

        </head>
        <body class="<?php echo DEFAULT_LOCALE_IN_USE;?><?php echo $session->isLoggedIn()?' logged':'';?>">
        	<img src="https://s3.amazonaws.com/todopro.com/images/Todo-Cloud-Logo-200.png" style="position:absolute;top:-300px;left:-300px"/>
        	<!-- FB Like Button -->
        	<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=202988483161759";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php if(isset($_SESSION['info_messages']) && $_SESSION['info_messages']) : ?>
    <script>
        var info_message_text = '<?php echo $_SESSION['info_messages']['message']; ?>';
        var info_message_type = '<?php echo $_SESSION['info_messages']['type']; ?>';
    </script>
<?php
    unset($_SESSION['info_messages']);
endif; ?>

        	<!-- version info -->
        	<input type="hidden" id="todopro_version" value="<?php echo TDO_VERSION;?>" />
        	<input type="hidden" id="todopro_revision" value="<?php echo TDO_REVISION;?>" />

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
	             function setPageTitle($title)
            	{
            		echo '<script>document.getElementById("page_title").innerHTML = "'.$title.'";</script>';
            	}

                if (isset($_GET['todo-for-business'])) {
                    include_once('TodoOnline/content/LandingSignUpTFBContent.php');
                } else {
                    if (!$session->isLoggedIn()) {
                        if ($session->isFB()) {
                            $signedRequest = $facebook->getSignedRequest();
                            if ($signedRequest && isset($signedRequest['oauth_token'])) {
                                //The user is already logged into facebook, so automatically log them in using their facebook credentials
                                loginFacebookUser($session, $facebook);
                            } else {
                                $redirectURL = "";
                                if (isset($_GET['oauth_fb_callback'])) {
                                    $redirectURL = "https://wwww.facebook.com";
                                } else {
                                    //Get the URL needed to authorize the app with facebook
                                    if (isset($_SERVER['REQUEST_URI'])) {
                                        $serverrequri = $_SERVER['REQUEST_URI'];
                                    } elseif (isset($_SERVER['PHP_SELF'])) {
                                        $serverrequri = $_SERVER['PHP_SELF'];
                                    }
                                    //Add oauth_fb_callback to the parameters so we don't keep redirecting
                                    $separator = "?";
                                    if (strpos($serverrequri, "?") !== false)
                                        $separator = "&";

                                    // Find the location for the new parameter
                                    $insertPosition = strlen($serverrequri);
                                    if (strpos($serverrequri, "#") !== false)
                                        $insertPosition = strpos($serverrequri, "#");

                                    // Build the new url
                                    $serverrequri = substr_replace($serverrequri, $separator . "oauth_fb_callback=true", $insertPosition, 0);

                                    $params = array('redirect_uri' => FB_REDIRECT_URL . $serverrequri, "scope" => "email");
                                    $redirectURL = $facebook->getLoginUrl($params);
                                }

                                echo "<script type=\"text/javascript\">top.location='" . $redirectURL . "';</script>";
                                echo "</body></html>";
                                exit();

                            }
                        } else {
                            if (isset($_GET['error'])) {
                                echo "<script type=\"text/javascript\">alert('Facebook login failed')</script>";
                            } elseif (isset($_GET['code'])) //This is given to us by facebook after the user authorizes
                            {
                                loginFacebookUser($session, $facebook);
                            } elseif (isset($_GET['acceptinvitation']) && ($GLOBALS['isIOS'] == true) && !isset($_GET['appNotInstalled'])) {
                                // Load a script that will attempt to launch the
                                // app on iOS and if not it will redirect back to
                                // the original URL.

                                // If the script fails to open the app, we need to
                                // have it redirect to the original URL. The user
                                // will be prompted to sign in to the app to accept
                                // the invitation instead, but we'll have to set the
                                // appNotInstalled parameter so that this same script
                                // doesn't get run.

                                $invitationid = $_GET["invitationid"];
                                $invitationURL = SITE_PROTOCOL . SITE_BASE_URL . "?acceptinvitation=true&invitationid=" . $invitationid . "&appNotInstalled=true";

                                //error_log("INVITATION URL: " . $invitationURL);

                                echo "<script type=\"text/javascript\">\n";
                                echo "setTimeout(function() {\n";
                                //echo "    if (new Date().valueOf() - now > 100)\n";
                                //echo "    {\n";
                                //echo "        window.close();\n"; // Do nothing because the iOS app must have launched
                                //echo "        return;\n";
                                //echo "    }\n";
                                echo "    window.location = \"" . $invitationURL . "\";\n";
                                echo "}, 25);\n";
                                echo "window.location = \"appigotodov3://x-callback-url/showListInvitations\";\n";
                                echo "</script>\n";

                                //echo "<p>Invitation URL: ". $invitationURL ."</p>\n";

                                echo "</body></html>\n";

                                exit();
                            }
                        }
                    }

                    if ($session->isLoggedIn()) {

                        //Commenting out these timezone changes because they could cause problems in cases where users
                        //have never set up their timezone settings and we have inferred their timezone incorrectly.
                //                	$userSettings = TDOUserSettings::getUserSettingsForUserid($session->getUserId());
                //                	$tz= $userSettings->timezone();
                //                	$utc = new DateTimeZone('UTC');
                //                	$dt = new DateTime('now', $utc);
                //                	$current_tz = new DateTimeZone($tz);
                //                    $offset =  $current_tz->getOffset($dt);

                        //$loadContent = true;
                        echo '<input id="userId" type="hidden" value="' . $session->getUserId() . '" />';
                        echo '<input id="userName" type="hidden" value="' . TDOUser::displayNameForUserId($session->getUserId()) . '" />';

                        $user = TDOUser::getUserForUserId($session->getUserId());
                        $userPicUrl = $user->fullImageURL();
                        echo '<input id="userImgUrl" type="hidden" value="' . $userPicUrl . '" />';
                //                    echo '<input id="timezone" type="hidden" value="'.$offset.'"/>';

                        if (isset($_COOKIE['TodoOnlineListId'])) {
                            $listId = $_COOKIE['TodoOnlineListId'];
                            $listColor = 'noColor';

                            if ($listId != 'all' && $listId != 'today' && $listId != 'focus' && $listId != 'starred') {
                                $listSettings = TDOListSettings::getListSettingsForUser($listId, $session->getUserId());
                                if (!empty($listSettings))
                                    $listColor = $listSettings->color();
                            }

                            echo '<input id="listId" type="hidden" value="' . $listId . '" listColor="' . $listColor . '" />';
                        }
                        $messages = $user->userMessages();
                        if ($messages && is_array($messages)) {
                            if (array_key_exists(USER_ACCOUNT_MESSAGE_TRIAL_ONE, $messages) && $messages[USER_ACCOUNT_MESSAGE_TRIAL_ONE] == 1) {
                                ?>
                                <div class="welcome-message hidden" data-message-name="<?php echo USER_ACCOUNT_MESSAGE_TRIAL_ONE; ?>">
                                    <div class="modal-header">
                                        <?php _e('Welcome From Appigo&#39;s CEO'); ?>
                                    </div>
                                    <div class="modal-content">
                                        <div class="welcome-message">
                                            <h3><?php printf(_('Welcome %s!'), TDOUser::displayNameForUserId($session->getUserId())); ?></h3>

                                            <p><?php _e('Todo is one of the most powerful and affordable task and project tracking systems available and now the business version extends it to your team.'); ?></p>

                                            <p><?php _e('There are 3 things you should know to get started:'); ?></p>
                                            <ol>
                                                <li><?php printf(_('1. Support Site - Set up %sTodo for Business%s'), '<a href="http://support.appigo.com/solution/articles/4000065339-4-steps-to-set-up-todo-for-business" target="_blank">', '</a>'); ?></li>
                                                <li><?php printf(_('2. Premium Support - Email %sbusiness@appigo.com%s for a very quick and detailed response'), '<a href="mailto:business@appigo.com">', '</a>'); ?></li>
                                                <li><?php printf(_('3. Business setup support - email %ssupport@appigo.com%s for help or suggestions on using Todo specifically for your business.'), '<a href="mailto:support@appigo.com">', '</a>'); ?></li>
                                            </ol>

                                            <p><?php _e('In the next week or two you will be receiving a few emails to help you be successful with Todo.'); ?></p>

                                            <footer><?php printf(_('Thanks!%sTravis Cook%sAppigo CEO'), '<br/>', '<br/>'); ?></footer>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div class="button btn-cancel-modal"><?php _e('OK');?></div>
                                    </div>
                                </div>
                                <?php
                            } elseif (array_key_exists(USER_ACCOUNT_MESSAGE_TRIAL_MANY, $messages) && $messages[USER_ACCOUNT_MESSAGE_TRIAL_MANY] == 1) {
                                ?>
                                <div class="welcome-message hidden" data-message-name="<?php echo USER_ACCOUNT_MESSAGE_TRIAL_MANY; ?>">
                                    <div class="modal-header">
                                        <?php _e('Welcome From Appigo&#39;s CEO'); ?>
                                    </div>
                                    <div class="modal-content">
                                        <div class="welcome-message">
                                            <h3><?php printf(_('Welcome %s!'), TDOUser::displayNameForUserId($session->getUserId())); ?></h3>

                                            <p><?php _e('Todo is one of the most powerful and affordable task and project tracking systems available and now the business version extends it to your team.'); ?></p>

                                            <p><?php _e('There are 3 things you should know to get started:'); ?></p>
                                            <ol>
                                                <li><?php printf(_('1. Support Site - Set up %sTodo for Business%s'), '<a href="http://support.appigo.com/solution/articles/4000065339-4-steps-to-set-up-todo-for-business" target="_blank">', '</a>'); ?></li>
                                                <li><?php printf(_('2. Premium Support - Email %sbusiness@appigo.com%s for a very quick and detailed response'), '<a href="mailto:business@appigo.com">', '</a>'); ?></li>
                                                <li><?php printf(_('3. Business setup support - email %ssupport@appigo.com%s for help or suggestions on using Todo specifically for your business.'), '<a href="mailto:support@appigo.com">', '</a>'); ?></li>
                                            </ol>

                                            <p><?php _e('In the next week or two you will be receiving a few emails to help you be successful with Todo.'); ?></p>

                                            <footer><?php printf(_('Thanks!%sTravis Cook%sAppigo CEO'), '<br/>', '<br/>'); ?></footer>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div class="button btn-cancel-modal"><?php _e('OK');?></div>
                                    </div>
                                </div>
                                <?php
                            } elseif (array_key_exists(USER_ACCOUNT_MESSAGE_CURRENT, $messages) && $messages[USER_ACCOUNT_MESSAGE_CURRENT] == 1) {
                                ?>
                                <div class="welcome-message hidden" data-message-name="<?php echo  USER_ACCOUNT_MESSAGE_CURRENT; ?>">
                                    <div class="modal-header">
                                        <?php _e('Welcome From Appigo&#39;s CEO'); ?>
                                    </div>
                                    <div class="modal-content">
                                        <div class="welcome-message">
                                            <h3><?php printf(_('Welcome %s!'), TDOUser::displayNameForUserId($session->getUserId())); ?></h3>

                                            <p><?php _e('I&#39;m so glad you&#39;re giving us a try. Todo is one of the most powerful and affordable task and project tracking systems available and now the business version extends it to your team. I noticed you are a current user and I thank you for trusting us with your productivity.'); ?></p>

                                            <p><?php _e('To get you started quickest I want to offer you a personal invitation – please tell us a little bit about your business and what you want to accomplish by using Todo and we will promptly reply with an ideal set up and customized instructions and steps. I personally want to make sure you can take advantage of and understand the full capabilities of Todo for Business.'); ?></p>

                                            <p><?php _e('In the next week or two you will be receiving very few but hopefully very helpful emails to help you get up and running and successful.'); ?></p>

                                            <p><?php _e('Also, over the next few months we will be engaging with some of our teams to get direct input on what you would like to see our platform do for you. Let me know if you have interest in this.'); ?></p>

                                            <p><?php printf(_('Last, let me know what questions or concerns you may have also. We have a special support email address that will get you quickest response: %sbusiness-support@appigo.com%s.'), '<a href="mailto:business-support@appigo.com">', '</a>'); ?></p>

                                            <footer><?php printf(_('Thanks!%sTravis Cook%sAppigo CEO'), '<br/>', '<br/>'); ?></footer>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <div class="button btn-cancel-modal"><?php _e('OK');?></div>
                                    </div>
                                </div>
                                <?php
                            }
                        }

                        /*
                $topRightLinksHTML = '';


                        $username = TDOUser::displayNameForUserId($session->getUserId());
                        $logOutHtml = '';
                        if($session->isLoggedIn() && !$session->isFB())
                        {
                            $logOutHtml .= '	<label><a href="?method=logout">Logout</a></label>';
                        }
                        //build links that will go on the top right of the page
                        $topRightLinksHTML .= ' 	<div class="header_links">';
                        $topRightLinksHTML .= '			<a href="javascript:displaySendFeedbackModal()" style="color:white;margin-right:20px;">Send Feedback</a>';
                        $topRightLinksHTML .= '			<div class="property_wrapper">';
                        $topRightLinksHTML .= '				<div class="property_icon" id="user_options_toggle">';
                        $topRightLinksHTML .= '					<a href="javascript:displayUserOptions()">'.$username.'</a>';
                        $topRightLinksHTML .= '				</div>';
                        $topRightLinksHTML .= '				<div class="property_flyout user_options_flyout" id="user_options_flyout">';
                        $topRightLinksHTML .= '					<label><a href="?appSettings=show&option=general">Settings</a></label>';
                        $topRightLinksHTML .= 					$logOutHtml;
                        $topRightLinksHTML .= '				</div>';
                        $topRightLinksHTML .= '			</div>';
                        $topRightLinksHTML .= '	</div>';
                */

                        //$topRightLinksHTML .= ' 	<style>#login_form span, #login_form a{margin-left:6px;}</style>';
                        //$topRightLinksHTML .= ' 	<a href="."><span id="userDisplayName">'.$username.'</span></a>';
                        //$topRightLinksHTML .= ' 	<span> � </span>';
                        //$topRightLinksHTML .= ' 	';
                        //$topRightLinksHTML .= ' 	<span> � </span>';
                        //$topRightLinksHTML .= '		';


                        //echo $topRightLinksHTML;

                        //echo '<div id="main_container">';
                        if ($GLOBALS['isIOS'] == true)
                            loadSignedInContentiOS($session);
                        else
                            loadSignedInContent($session, $showTaskHtml);

                    } else {

                        if ($session->isFB() == false) {
                            if (isset($_SESSION['ref']))
                                $referrer = $_SESSION['ref'];
                            else
                                $referrer = ".";
                        }


                        /*
                if(!empty($loginError))
                        {
                            $modalWindow = array(	"id"=>"loginErrorModal",
                                                        "title"=>"Login Error",
                                                        "body"=>"$loginError",
                                                        "cancel_button_label"=>"OK");
                                //We're not doing modal windows in php any more. Implement it in javascript.
                                include('TodoOnline/content/???.php');
                                echo  "<script type=\"text/javascript\">displayModalWindow('loginErrorModal');</script>";
                        }
                */

                        //If we're in facebook and we're not logged in, don't show the login page
                        if ($session->isFB()) {
                            //NCB - Taking out Facebook integration for initial release.
                            _e('Todo Cloud does not currently run in Facebook.');

                            //include_once('TodoOnline/content/InviteOnlyContent.php');
                        } else {
                            TDOSession::saveCurrentURL();
                            include_once('TodoOnline/content/LoginContent.php');
                        }

                        if (isset($_GET['referralunsubscribe'])) {
                            setPageTitle('Todo Cloud Referrals - Unsubscribe');
                            include_once('TodoOnline/content/UnsubscribeReferralsContent.php');
                        }
						else if(isset($_GET['optOutEmails']))
						{
							include_once('TodoOnline/content/OptOutEmailsContent.php');
						}
						else
						{
                            loadLandingScreen($session);

                            if (isset($_GET['resetpassword']))
                                loadResetPasswordScreen();
                        }
                    }
                }
                function loadResetPasswordScreen()
                {
                	$hiddenInputHtml = '';
                	$errorMsg = '';

                	if(isset($_GET['uid']) && isset($_GET['resetid']))
                	{
                		$hiddenInputHtml .= '				<input id="uid" value="'.$_GET['uid'].'" type="hidden" />';
                		$hiddenInputHtml .= '				<input id="resetid" value="'.$_GET['resetid'].'" type="hidden" />';

                        $screenHtml = '<script src="' . TP_JS_PATH_RESET_PASSWORD_FUNCTIONS . '"></script>';

                        echo $hiddenInputHtml;
                        echo $screenHtml;
                	}
                	else
                	{
                		$errorMsg = _('Your link to reset your password appears to be broken');
                		error_log($errorMsg);
                	}


                }

                function loadLandingScreen($session)
                {
                    if (isset($_GET['sign-in']) || isset($_GET['verifyemail'])) {
                        include_once('TodoOnline/content/LandingSignInContent.php');
                    } elseif (isset($_GET['todo-for-business'])) {
                        include_once('TodoOnline/content/LandingSignUpTFBContent.php');
                    } else {
                        include_once('TodoOnline/content/LandingPageContent.php');
                    }

					if(isset($_GET['verifyemail']))
					{
						include_once('TodoOnline/content/VerifyEmailContent.php');
					}
                }

                function loadSignedInContentiOS($session)
                {
                    if(isset($_GET['resetpassword']))
                    {
                        echo '<script type="text/javascript">top.location="?method=logout";</script>';
                        exit();
                    }
                    include_once('TodoOnline/content/LandingPageiOSContent.php');

                    if(isset($_GET['acceptinvitation']))
                    {
                        include_once('TodoOnline/content/AcceptInvitationContent.php');
                    }
					else if(isset($_GET['acceptTeamInvitation']))
					{
						include_once('TodoOnline/content/AcceptTeamInvitationContent.php');
					}
					else if(isset($_GET['optOutEmails']))
					{
						include_once('TodoOnline/content/OptOutEmailsContent.php');
					}
                }

                function loadSignedInContent($session, $showTaskHtml)
                {

                	$pageTitle = 'Todo Cloud';
                	$mainContainerHTML = '';

	                echo $mainContainerHTML;
	                $topLeftLinksHTML = '';
	                $topRightLinksHTML = '';


					$userID = $session->getUserId();
					$username = TDOUser::displayNameForUserId($userID);
					$logOutHtml = '';
					if($session->isLoggedIn() && !$session->isFB())
					{
						$logOutHtml .= '	<label><a href="?method=logout">' . _('Logout') . '</a></label>';
					}


					//build links that will go on the top right of the page
					//$topRightLinksHTML .= '			<a href="javascript:displaySendFeedbackModal()" style="color:white;margin-right:20px;">Send Feedback</a>';
					$topRightLinksHTML .= '			<div class="property_wrapper user_settings_sign_out_button" >';
						// If the team member is a team admin, show an "Invite Team Members" button
                    $teamID = TDOTeamAccount::getTeamIDForUser($userID, TEAM_MEMBERSHIP_TYPE_ADMIN);
                    if (!empty($teamID)) {
                        $topRightLinksHTML .= '             <a href="/?appSettings=show&option=teaming&action=teaming_members#invited-members-btn" class="btn-default btn-size-xs btn-info btn-invite-member">' . _('Invite a Team Member') . '</a>';
                    } else {
                        if (!TDOTeamAccount::isTeamMember($userID)) {
                            $topRightLinksHTML .= '             <a href="/?appSettings=show&option=teaming&action=createTeam" class="btn-default btn-size-xs btn-info" onClick="ga(\'send\', \'event\', \'Create a Team button\', \'Click\', \'Create a Team\')">' . _('Create a Team') . '</a>';
                        }else{
                            $topRightLinksHTML .= '             <a href="/?appSettings=show&option=teaming&action=createTeam" class="btn-default btn-size-xs btn-info">' . _('Team') . '</a>';
                        }
                    }
					
					$topRightLinksHTML .= '				<a class="user_settings_button" href="?appSettings=show&option=general">' . _('Settings') . '</a>';
					$topRightLinksHTML .= '				<div class="property_icon " id="user_options_toggle" onclick="displayUserOptions(event)">';
					$topRightLinksHTML .= '					<div class="icon"></div>';
					//$topRightLinksHTML .= '					<a href="javascript:displayUserOptions()">'.$username.'</a>';
					$topRightLinksHTML .= '					<span id="unreadMessagesSettingsBadge" class="settings-alert"></span>';
					$topRightLinksHTML .= '				</div>';
					$topRightLinksHTML .= '				<div class="property_flyout user_options_flyout" id="user_options_flyout">';
					$topRightLinksHTML .= '					<label><a href="?appSettings=show&option=general">' . _('Settings') . '</a></label>';
					$topRightLinksHTML .= '					<label><a href="javascript:void(0)" onclick="hideUserOptions(event); displayAboutModal(event)">' . _('About') . '</a></label>';
					$topRightLinksHTML .= '					<label><a href="javascript:void(0)" onclick="hideUserOptions(event); displaySendFeedbackModal();">' . _('Feedback') . '</a></label>';
					$topRightLinksHTML .= '					<label><a href="http://support.appigo.com/" target="_blank">' . _('Help & Support') . '</a></label>';

                    if (TDOTeamAccount::isTeamMember($userID)) {
                        $teamID = TDOTeamAccount::getTeamIDForUser($userID, TEAM_MEMBERSHIP_TYPE_ADMIN);
                        if (!empty($teamID)) {
                            $topRightLinksHTML .= '			<label><a href="mailto:support@appigo.com">' . _('Contact support') . '</a></label>';
                        }
                    } else {
                        $topRightLinksHTML .= '				<label><a href="mailto:support@appigo.com">' . _('Contact support') . '</a></label>';
                    }


					//$topRightLinksHTML .= '					<label><a href="?beta=kingkong">BETA Info</a></label>';
					$topRightLinksHTML .= 					$logOutHtml;
					$topRightLinksHTML .= '				</div>';
					$topRightLinksHTML .= '			</div>';

                    //Show the system notification if there is one and it hasn't been hidden by the user
                    $systemNotification = TDOSystemNotification::getCurrentSystemNotification();
                    $systemNotificationHTML = '';
                    if($systemNotification != NULL)
                    {
                        if(!isset($_COOKIE['HiddenSystemNotificationId']) || $_COOKIE['HiddenSystemNotificationId'] != $systemNotification->notificationId())
                        {
                            $systemNotificationHTML = '<div id="system_notification_container">';
							$systemNotificationHTML .= '<div id="system_notification_title" onclick="showSystemNotificationMessage();">' . _('Service Alert') . '</div>';
							$systemNotificationHTML .= '<input type="hidden" id="system_notification_message" value="'.$systemNotification->message().'" />';

                            if($systemNotification->learnMoreUrl())
                            {
								$systemNotificationHTML .= '<input type="hidden" id="system_notification_learn_more_link" value="'.$systemNotification->learnMoreUrl().'" />';
                            }

							$systemNotificationHTML .= '<input type="hidden" id="system_notification_id" value="' . $systemNotification->notificationId() . '" />';
                            $systemNotificationHTML .= '</div>';
                        }
                    }

                    //Add hidden input elements for all new features, so the UI will know which ones to show
                    $currentDisplayedFeatureFlags = TDOUserSettings::getCurrentNewFeatureFlagsForUser($userID);
                    $newFeaturesArray = array(
                                                array("input_id" => "new_feature_flag_referrals", "flag" => NEW_FEATURE_FLAG_REFERRALS)
//                                                array("input_id" => "new_feature_flag_test1", "flag" => NEW_FEATURE_FLAG_TEST1),
//                                                array("input_id" => "new_feature_flag_test2", "flag" => NEW_FEATURE_FLAG_TEST2),
//                                                array("input_id" => "new_feature_flag_test3", "flag" => NEW_FEATURE_FLAG_TEST3),
                                              );
                    foreach($newFeaturesArray as $newFeature)
                    {
                        if(isset($newFeature['input_id']) && isset($newFeature['flag']))
                        {
                            $value = 0;
                            //Bitwise AND the current flag we're looking at with the flags that should be currently displayed
                            if($currentDisplayedFeatureFlags & $newFeature['flag'])
                                $value = 1;

                            echo '<input type="hidden" id="'.$newFeature['input_id'].'" value='.$value.' />';
                        }
                    }

                    //Add code for showing unread message badge
                    include_once('TodoOnline/messageCenter/MCUserMessage.php');
                    if(MCUserMessage::userShouldCheckUnreadMessageCount() == false)
                    {
                        $count = $_SESSION['unread_message_count'];
                        echo '<input type="hidden" id="unread_message_count" value="'.$count.'" />';
                    }

	                //build auxiliary html elements
	                $mainContainerHTML = '';
                	$mainContainerHTML .= '	';
                	$mainContainerHTML .= '	<div id="alertsContainer"></div>';
                	$mainContainerHTML .= '	<div id="alertSoundPlayer"></div>';

									// header
                    if ($userID) {
                        $user = TDOUser::getUserForUserId($userID);
                        if ($user) {
                            $verifiedEmail = $user->emailVerified();
                            $username = htmlspecialchars($user->username());
                            if (!$verifiedEmail) {
                                $mainContainerHTML .= '<div id="info-header" class="email-confirmation-wrapper">';
                                $mainContainerHTML .= '<p>' . _('Confirm your email address to access all of Todo Cloud&#39;s features. A confirmation email was sent to ') . $username.'</p>';
                                $mainContainerHTML .= '<button type="button" onclick="verifyUserEmail()">' . _('Resend confirmation') . '</button>';
                                $mainContainerHTML .= '<a href="/?appSettings=show&option=account">' . _('Update email address') . '</a>';
                                $mainContainerHTML .= '<a href="http://support.appigo.com/">' . _('Learn more').'</a>';
                                $mainContainerHTML .= '</div>';
                            }
                        }
                    }

                	$mainContainerHTML .= '	<div id="header" class="main container header">';
                        if (isset($_COOKIE[TDO_ADMIN_IMPERSONATION_SESSION_COOKIE]) && $_COOKIE[TDO_ADMIN_IMPERSONATION_SESSION_COOKIE] == TRUE) {
                            $mainContainerHTML .=
                                '<div class="impersonation-section"><span>Warning! You are in Impersonation mode.</span><a href="/?method=logout" class="btn-default btn-warning btn-size-xs">Logout</a></div>';
                        }
                    $mainContainerHTML .= 		$topLeftLinksHTML;
                    $mainContainerHTML .= 		$topRightLinksHTML;
                	$mainContainerHTML .= '		<a class="app_logo container" href=".">';
	                $mainContainerHTML .= '		</a>';
                	// $mainContainerHTML .= '		<div id="search_wrap" class="search_wrap container"></div>';
                  $mainContainerHTML .= 		$systemNotificationHTML;
                	// $mainContainerHTML .= '		<div class="feature_vote_link" onclick="showFeatureVoteOptions()">Vote for upcoming features...</div>';
                	// $mainContainerHTML .= '		<div class="social_links" style="float:right;position:relative;top:10px">';
                	// $mainContainerHTML .= '			<div class="fb-like" data-href="https://www.todo-cloud.com/" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true"></div>';
                	// $mainContainerHTML .= '			<span class="tweet" style="position:relative;top:3px"><a href="https://twitter.com/share" class="twitter-share-button" data-url="https://www.todo-cloud.com/" data-text="Todo Cloud - Finally, productivity simple enough for you, your friends, and your life." data-dnt="true"></a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></span>';
                	// $mainContainerHTML .= '		</div>';
								  $mainContainerHTML .= '	</div>';
                    
	           		$mainContainerHTML .= '	<div id="controls" class="main container filter_controls_wrap" style="height: 100%;">';


	                echo $mainContainerHTML;
	                $mainContainerHTML = '';


	                //load html for controls in left column
					if(isset($_GET['appSettings']))
						include_once('TodoOnline/controls/AppSettingsControls.php');
                    else if(isset($_GET['showProperties']))
                    {
                        if($_GET['showProperties'] == 'list')
                            include_once('TodoOnline/controls/ListControls.php');
                        else
                            include_once('TodoOnline/controls/DashboardControls.php');

//                        if(isset($_COOKIE['TodoOnlineListId']))
//                            include_once('TodoOnline/controls/ListControls.php');
//                        elseif(isset($_COOKIE['TodoOnlineContextId']))
//                            include_once('TodoOnline/controls/ContextControls.php');
//                        elseif(isset($_GET['tag']))
//                            include_once('TodoOnline/controls/TagControls.php');
//                        else
//                            include_once('TodoOnline/controls/DashboardControls.php');
                    }
                   // else if(isset($_GET['applepromo']))
                   // {
	               //     include_once('TodoOnline/controls/AppSettingsControls.php');
                    //}
                    else
                        include_once('TodoOnline/controls/DashboardControls.php');


	                $mainContainerHTML .= '	</div>';
	            	$mainContainerHTML .= '	<div id="content" class="main container content_wrap" style="height: 100%;">';
	            	echo $mainContainerHTML;
	                $mainContainerHTML = '';

//NCB - Taking out Facebook integration for initial release.
//                    if(isset($_GET['request_ids']))
//                    {
//                        echo '<script type="text/javascript" src="' . TP_JS_PATH_INVITATION_FUNCTIONS . '"></script>';
//                        echo '<script type="text/javascript">displayFacebookRequestsModal();</script>';
//                    }


                    if ((isset($_GET['appSettings'])))
                    {
                        if(isset($_GET['option']))
                        {
                            $settingOption = $_GET['option'];

                            switch ($settingOption)
                            {
                                case 'general':
                                    include_once('TodoOnline/content/AppSettingsContentGeneral.php');
                                    break;
                                case 'focus':
                                    include_once('TodoOnline/content/AppSettingsContentFocus.php');
                                    break;
                                case 'taskcreation':
                                    include_once('TodoOnline/content/AppSettingsContentTaskParsing.php');
                                    break;
                                case 'notifications':
                                    include_once('TodoOnline/content/AppSettingsContentNotifications.php');
                                    break;
//                                case 'messagecenter':
//                                    include_once('TodoOnline/content/AppSettingsContentMessageCenter.php');
//                                    break;
                                case 'account':
                                    include_once('TodoOnline/content/AppSettingsContentAccount.php');
                                    break;
                                case 'subscription':
                                    include_once('TodoOnline/content/AppSettingsContentSubscription.php');
                                    break;
                                case 'invitations':
                                    include_once('TodoOnline/content/AppSettingsContentInvitations.php');
                                    break;
                                case 'gifts':
                                	include_once('TodoOnline/content/AppSettingsContentGifts.php');
                                	break;
								case 'referrals':
									include_once('TodoOnline/content/AppSettingsContentReferrals.php');
									break;
								case 'teaming':
									include_once('TodoOnline/content/AppSettingsContentTeaming.php');
									break;
                                default:
                                    include_once('TodoOnline/content/AppSettingsContentGeneral.php');
                                    break;
                            }
                        }
                    }
                    else if(isset($_GET['showProperties']))
                    {
                        if($_GET['showProperties'] == 'list')
                        {
                            if(isset($_GET['listsettings']))
                            {
                                setPageTitle($pageTitle . ' | ' .$listName .'- Settings');
                                include_once('TodoOnline/content/ListSettingsContent.php');
                            }
                            else if (isset($_GET['members']))
                            {
                                setPageTitle($pageTitle . ' | ' .$listName .'- Members');
                                include_once('TodoOnline/content/PeopleContent.php');
                            }
                            else if (isset($_GET['listhistory']))
                            {
                                setPageTitle($pageTitle . ' | ' .$listName .'- History');
                                include_once('TodoOnline/content/ListHistoryContent.php');
                            }
                        }
                    }
					else if(isset($_GET['optOutEmails']))
					{
						include_once('TodoOnline/content/OptOutEmailsContent.php');
					}
					else
					{
                        echo '<script type="text/javascript">';
                        if(isset($_GET['showtask']))
                        {
                            if($showTaskHtml != NULL)
                            {
                                echo $showTaskHtml;
                            }
                            else
                            {
                                echo 'alert("The task you are looking for could not be found");';
                                echo 'var taskIdToHighlight=null;';
                                echo 'var parentIdToHighlight=null;';
                                echo 'var taskitoIdToHighlight=null;';
                            }
                        }
                        else
                        {
                            echo 'var taskIdToHighlight=null;';
                            echo 'var parentIdToHighlight=null;';
                            echo 'var taskitoIdToHighlight=null;';
                        }
                        echo '</script>';

                        if(isset($_COOKIE['TodoOnlineListId']))
						{
	                        echo '<input type="hidden" id="viewType" value="list"/>';
	                        echo '<input type="hidden" id="listid" value="'.$_COOKIE['TodoOnlineListId'].'"/>';
	                    }
                        else
                        {
	                        echo '<input type="hidden" id="viewType" value="list"/>';
	                        echo '<input type="hidden" id="listid" value="all"/>';
//                            echo  '<input type="hidden" id="viewType" value="dashboard"/>';
//                            echo  '<input type="hidden" id="defaultlistid" value="'.TDOList::getUserInboxId($session->getUserId()).'"/>';
                        }

                        if(isset($_GET['mobile']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('Mobile'));
                            include_once('TodoOnline/content/MobileContent.php');
                        }
                        else if(isset($_GET['acceptinvitation']))
                        {
                            include_once('TodoOnline/content/AcceptInvitationContent.php');
                        }
						else if(isset($_GET['acceptTeamInvitation']))
						{
							include_once('TodoOnline/content/AcceptTeamInvitationContent.php');
						}
                        else if(isset($_GET['verifyemail']))
                        {
                            include_once('TodoOnline/content/VerifyEmailContent.php');
                        }
                        else if(isset($_GET['settings']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('Account Settings'));
                            include_once('TodoOnline/content/SettingsContent.php');
                        }
						else if(isset($_GET['applypromocode']))
						{
							error_log('applying promo code in pageloader.php');
							setPageTitle($pageTitle . ' | ' . _('Apply Promo Code'));
							include_once('TodoOnline/content/ApplyPromoCodeContent.php');
						}
                        else if(isset($_GET['applygiftcode']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('Apply Gift Code'));
                            include_once('TodoOnline/content/ApplyGiftCodeContent.php');
                        }
						else if(isset($_GET['referralcode']))
						{
							setPageTitle($pageTitle . ' | ' . _('Apply Referral Link'));
							include_once('TodoOnline/content/ApplyReferralLinkContent.php');
						}
						//else if(isset($_GET['applepromo'])  && TDOUtil::isCurrentUserInWhiteList($session))
						//{
						//	setPageTitle($pageTitle . ' | Apple Employee Promo');
						//	include_once('TodoOnline/content/AppleEmployeePromoContent.php');
						//}
                        else if(isset($_GET['beta']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('Beta Info'));
                            include_once('TodoOnline/content/BetaInfoContent.php');
                        }
                        else if(isset($_GET['about']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('About'));
                            error_log('loading about page');
                            include_once('TodoOnline/content/AboutContent.php');
                        }
                       // elseif(isset($_GET['tasks']))
                        //{
                        //    setPageTitle($pageTitle . ' | Tasks');
                        //    include_once('TodoOnline/content/TasksContent.php');
                        ///}
                        else if(isset($_GET['presentsubscriptioninvitation']))
                        {
                            setPageTitle($pageTitle . ' | ' . _('Subscription Information'));
                            include_once('TodoOnline/content/PresentSubscriptionInvitationContent.php');
                        }
                        else if(isset($_COOKIE['TodoOnlineListId']))
						{
	                        $listName = htmlspecialchars(TDOList::getNameForList($_COOKIE['TodoOnlineListId']));

	                        setPageTitle($pageTitle . ' | ' .$listName);
	                        include_once('TodoOnline/content/TasksContent.php');
	                    }
	                    else
                        {
                            setPageTitle($pageTitle . ' | ' . _('All'));
                            include_once('TodoOnline/content/TasksContent.php');
                        }

                    }

	                //echo '	</div>';
                }
            ?>

			<?php

				if($session->isLoggedIn())
				{
					// Show the system notification if one exists
					echo '<script>showInitialSystemNotificationMessageIfExists();</script>';
					//echo '<div id="FOOTER">
					//	<a href="?about=tuoba" >About</a>
					//	<span> - </span>
					//	<a href="?beta=kingkong">BETA Info</a>
					//	<span> - </span>
					//	<a href="javascript:void(0)" onclick="displaySendFeedbackModal()">Feedback</a>
					//</div>';
				}
			?>

        </body>
</html>

<?php

function loginFacebookUser($session, $facebook)
{
    //NCB - Taking out Facebook integration for initial release.
    return false;

    if($session->setupFacebookSession($facebook))
    {
        return true;
    }
    else
    {
        //Check for valid invitation before creating account
        TDOSession::saveCurrentURL();
//        if(TDOSession::currentURLHasFBRequest($facebook->getUser()) || TDOSession::savedURLHasInvitation())
//        {
            $result = $session->createFacebookUser($facebook);
            if($result > 0)
            {
                return true;
            }
            else
            {
                $message = "Facebook login was unsuccessful";
                if($result == NO_EMAIL_ERROR)
                {
                    $message = "Unable to retrieve email address from Facebook";
                }
                elseif($result == EMAIL_TAKEN_ERROR)
                {
                    $message = "Your email address has already been registered with Todo Cloud. If you already have a Todo Cloud account, you may link it to this Facebook account in Settings->Account at www.".SITE_BASE_URL;
                }
                elseif($result == EMAIL_TOO_LONG_ERROR)
                {
                    $message = "Your email address is too long to be used as a username";
                }
                elseif($result == FB_USER_EXISTS_ERROR)
                {
                    error_log("HandleLogin.php trying to create a user account for an existing Facebook account");
                }

                if($message)
                    echo "<script type=\"text/javascript\">alert('".$message."');</script>";

                return false;
            }
//        }
//        else
//        {
//            echo "<script type=\"text/javascript\">alert('Todo Cloud is currently joined by invitation only.');</script>";
//            return false;
//        }
    }
}


function setCookiesForShowTask($taskId, $session)
{
    $task = TDOTask::getTaskForTaskId($taskId);
    $taskito = NULL;

    if(empty($task))
    {
        $taskito = TDOTaskito::taskitoForTaskitoId($taskId);
        if(empty($taskito) || $taskito->deleted() == true)
            return NULL;

        $task = TDOTask::getTaskForTaskId($taskito->parentId());
    }

    if(!empty($task) && $task->deleted() == false)
    {
        $listid = $task->listId();
        if(!empty($listid))
        {
            if(TDOList::userCanViewList($listid, $session->getUserId()) == true)
            {
                if(setcookie('TodoOnlineListId', $listid))
                    $_COOKIE['TodoOnlineListId'] = $listid;
                else
                    return NULL;

                if(setcookie('TodoOnlineContextId', 'all'))
                    $_COOKIE['TodoOnlineContextId'] = 'all';
                else
                    return NULL;

                if(setcookie('TodoOnlineTagId', 'all'))
                    $_COOKIE['TodoOnlineTagId'] = 'all';
                else
                    return NULL;

                $taskToCheckCompletion = $task;
                $parentTaskId = $task->parentId();

                if(!empty($parentTaskId))
                {
                    $parent = TDOTask::getTaskForTaskId($parentTaskId);
                    if(!empty($parent) && $parent->deleted() == false)
                        $taskToCheckCompletion = $parent;
                    else
                        return NULL;
                }

                //If the task (or parent task) is not complete, make sure we're showing the active view
                if($taskToCheckCompletion->completionDate() == 0)
                {
                    if(setcookie('TodoOnlineShowCompletedTasks', 0))
                        $_COOKIE['TodoOnlineShowCompletedTasks'] = 0;
                    else
                        return NULL;
                }
                else
                {
                    //If the task's completion date is earlier than today, make sure we're showing the completed view
                    $todayMidnightDate = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
                    if($taskToCheckCompletion->completionDate() < $todayMidnightDate)
                    {
                        if(setcookie('TodoOnlineShowCompletedTasks', 1))
                            $_COOKIE['TodoOnlineShowCompletedTasks'] = 1;
                        else
                            return NULL;
                    }
                }

                $html = 'var taskIdToHighlight="'.$task->taskId().'";';

                if(!empty($parentTaskId))
                    $html .= 'var parentIdToHighlight="'.$parentTaskId.'";';
                else
                    $html .= 'var parentIdToHighlight=null;';

                if(!empty($taskito))
                    $html .= 'var taskitoIdToHighlight="'.$taskito->taskitoId().'";';
                else
                    $html .= 'var taskitoIdToHighlight=null;';

                return $html;
            }
        }
    }

    return NULL;
}

function setCookiesForShowList($listid, $session)
{
    if (!empty($listid)) {
        if (TDOList::userCanViewList($listid, $session->getUserId()) == true) {
            if (setcookie('TodoOnlineListId', $listid))
                $_COOKIE['TodoOnlineListId'] = $listid;
            else
                return NULL;

            if (setcookie('TodoOnlineContextId', 'all'))
                $_COOKIE['TodoOnlineContextId'] = 'all';
            else
                return NULL;
            if (setcookie('TodoOnlineTagId', 'all'))
                $_COOKIE['TodoOnlineTagId'] = 'all';
            else
                return NULL;
        }
    }
    return NULL;
}

?>

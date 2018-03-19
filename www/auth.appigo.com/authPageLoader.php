<?php

include_once('TodoOnline/base_sdk.php');

include_once('ssoMethods.php');  

$useCommonFields = false;

if(isset($_GET['page']))
{
   $page = $_GET['page'];
}

if(isset($_POST['page']))
{
    $page = $_POST['page'];
}
   
echo '<script src="js/AuthFunctions.js?v=3"></script>';
echo '<link type="text/css" rel="stylesheet" media="screen" href="css/auth.css?v=2" />';

if(isset($_GET['verifyemail']))
{
	echo '<section class="section-wrapper rounded-6 header-section">';
	echo '	<span class="company-logo">';
	echo '		<img src="http://cdn.freshdesk.com/data/helpdesk/attachments/production/10891222/logo/Appigo-Symbol-Small.png?1380663723" />';
	echo '		<h1>Appigo, Inc.</h1></span>';
	echo '</section>';
	
	echo '<section class="section-wrapper rounded-6">';
	echo '	<h2 class="section-heading">Email Verification</h2>';
    echo '	<div class="subsection">';
    
    $verificationId = $_GET['verificationid'];
    
    //Make sure the verification code matches the current user
    $verificationObj = AppigoEmailVerification::getEmailVerificationForVerificationId($verificationId);
    if(!empty($verificationObj))
    {
        // check to see if this verification code came from a web signup
        if($verificationObj->userId() == WEB_EMAIL_SIGNUP_USER)
        {
            error_log("Found verification entry for the user: " . WEB_EMAIL_SIGNUP_USER);
            // this email doesn't have a real user behind it, just a web form sign up
            if(AppigoEmailListUser::updateListUser($verificationObj->username(), EMAIL_CHANGE_SOURCE_WEB_PAGE))
            {
                $verificationObj->deleteEmailVerification();
                if(empty($userid) || empty($resetid))
                {
                    echo '<div>Your email has been verified.</div>';
                }
            }
            else
            {
                echo '<div>Unable to verify email address.</div>';
                error_log("Unable to update the list user: " . $verificationObj->username());
            }
        }
        else
        {
            //If we get here, the verification code checks out, so mark the user as verified
            $user = AppigoUser::getUserForUserId($verificationObj->userId());
            
            $user->setEmailVerified(1);
            
            if($user->updateUser())
            {
                $verificationObj->deleteEmailVerification();
                AppigoEmailListUser::updateListUser($verificationObj->username(), EMAIL_CHANGE_SOURCE_SUPPORT_ACCOUNT);
                echo '<div>Your email has been verified.</div>';
            }
            else
            {
                echo '<div>Unable to verify email address.</div>';
            }
        }
    }
    else
    {
        echo '<div>Unable to verify email address (verification code not valid).</div>';
    }

    echo '<div>Return to <a href="'.SUPPORT_SITE_FULL_URL.'">Appigo Support</a>.</div>';
    echo '</div></section>';    
}
else if(empty($page))
{
	
	echo '<section class="section-wrapper rounded-6 header-section">';
	echo '	<span class="company-logo">';
	echo '		<img src="http://cdn.freshdesk.com/data/helpdesk/attachments/production/10891222/logo/Appigo-Symbol-Small.png?1380663723" />';
	echo '		<h1>Appigo, Inc.</h1></span>';
	echo '</section>';
	
	echo '<section class="section-wrapper rounded-6">';
	echo '	<h2 class="section-heading">Log in</h2>';
    echo '	<div class="subsection">';
	echo '		<h3 class="subsection-heading">Enter your Appigo Support or Todo Cloud username and password</h3>';
	echo '		<div class="credentials-section">';
	echo '			<form name="form1">';
	echo '				<div class="input-section">';
	echo '					<label for="username">Username (Email address):</label>';
	echo '					<input name="username" type="text" id="username" onchange="validateEmail()" class="textbox rounded-6" />';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="password">Password:</label>';
	echo '					<input onkeydown="if ((event.keyCode == 13) || (event.which == 13)) signIn(); else return;" name="password" type="password" id="password" onchange="validatePasswords()" class="textbox rounded-6" />';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<span class="extra-links"><a href="?page=resetPasswordRequest">Forgot password?</a><br/><a href="?page=createAccount">Create an account</a>';
	echo '					</span>';
	echo '					<actionbutton onclick="signIn()" value="Login" id="button1" class="rounded-6">Log in</actionbutton>';
	echo '				</div>';
	echo '				<div>';
	echo '					<div class="status-message" id="status_message"></div>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="error-status-message" id="error_status_message"></div>';
	echo '				</div>';
	echo '			</form>';
	echo '		</div>';
	echo '	</div>';
	echo '</section>';
	echo '<script>window.onload=function(){document.form1.username.focus();}</script>';
	
}
else if($page == 'createAccount')
{
    
	echo '<section class="section-wrapper rounded-6 header-section">';
	echo '	<span class="company-logo">';
	echo '		<img src="http://cdn.freshdesk.com/data/helpdesk/attachments/production/10891222/logo/Appigo-Symbol-Small.png?1380663723" />';
	echo '		<h1>Appigo, Inc.</h1></span>';
	echo '</section>';
	
	echo '<section class="section-wrapper rounded-6">';
	echo '	<h2 class="section-heading">Create Account</h2>';
    echo '	<div class="subsection">';
	echo '		<h3 class="subsection-heading">Create an Appigo Support account</h3>';
	echo '		<div class="credentials-section">';
	echo '			<form name="form1" method="post">';
	echo '				<div class="input-section">';
	echo '					<label for="firstname">First Name:</label>';
	echo '					<input name="firstname" type="text" id="first_name" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="error-status-message" id="first_name_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="lastname">Last Name:</label>';
	echo '					<input name="lastname" type="text" id="last_name" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="error-status-message" id="last_name_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="username">Email address:</label>';
	echo '					<input name="username" type="text" id="username" onchange="validateEmail()" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="error-status-message" id="username_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="password">Password:</label>';
	echo '					<input name="password" type="password" id="password" onchange="validatePasswords()" class="textbox rounded-6" />';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="password_2">Verify Password:</label>';
	echo '					<input name="password_2" type="password" id="password_2" onchange="validatePasswords()" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="error-status-message" id="password_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="checkbox-input-section">';
	echo '					<input name="emailoptin" type="checkbox" id="emailoptin" class="textbox rounded-6" value="Receive email announcements" />';
	echo '					<label for="password_2">Receive Email Announcements</label>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<span class="extra-links"><a href=".">Login</a>';
	echo '					</span>';
	echo '					<actionbutton onclick="signUp()" value="Create" id="button1" class="rounded-6">Create</actionbutton>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="status-message" id="status_message"></div>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="error-status-message" id="error_status_message"></div>';
	echo '				</div>';
	echo '			</form>';
	echo '		</div>';
	echo '	</div>';
	echo '</section>';
	echo '<script>window.onload=function(){document.form1.firstname.focus();}</script>';

}
else if($page == 'resetPasswordRequest')
{
    
	echo '<section class="section-wrapper rounded-6 header-section">';
	echo '	<span class="company-logo">';
	echo '		<img src="http://cdn.freshdesk.com/data/helpdesk/attachments/production/10891222/logo/Appigo-Symbol-Small.png?1380663723" />';
	echo '		<h1>Appigo, Inc.</h1></span>';
	echo '</section>';
	
	echo '<section class="section-wrapper rounded-6">';
	echo '	<h2 class="section-heading">Password Reset</h2>';
    echo '	<div class="subsection">';
	echo '		<h3 class="subsection-heading">Reqeust a password reset email</h3>';
	echo '		<div class="credentials-section">';
	echo '			<form name="form1" method="post">';
	echo '				<div class="input-section">';
	echo '					<label for="username">Email address:</label>';
	echo '					<input name="username" type="text" id="username" onchange="validateEmail()" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="status-message" id="username_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<span class="extra-links"><a href=".">Login</a>';
	echo '					</span>';
	echo '					<actionbutton onclick="submitRequestResetPassword()" value="Create" id="button1" class="rounded-6">Request Email</actionbutton>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="status-message" id="status_message"></div>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="error-status-message" id="error_status_message"></div>';
	echo '				</div>';
	echo '			</form>';
	echo '		</div>';
	echo '	</div>';
	echo '</section>';
	echo '<script>window.onload=function(){document.form1.username.focus();}</script>';
	
}
else if($page == 'resetPassword')
{
	echo '<section class="section-wrapper rounded-6 header-section">';
	echo '	<span class="company-logo">';
	echo '		<img src="http://cdn.freshdesk.com/data/helpdesk/attachments/production/10891222/logo/Appigo-Symbol-Small.png?1380663723" />';
	echo '		<h1>Appigo, Inc.</h1></span>';
	echo '</section>';
	
	echo '<section class="section-wrapper rounded-6">';
	echo '	<h2 class="section-heading">Reset Password</h2>';
    echo '	<div class="subsection">';
	echo '		<h3 class="subsection-heading">Reset your Appigo Support Password</h3>';
    
    // verify that we have a valid reset password request
    if(isset($_POST['uid']))
        $userid = $_POST['uid'];
    else if(isset($_GET['uid']))
        $userid = $_GET['uid'];
    
    if(isset($_POST['resetid']))
        $resetid = $_POST['resetid'];
    if(isset($_GET['resetid']))
        $resetid = $_GET['resetid'];

    if(empty($userid) || empty($resetid))
    {
        echo '<div>The Link you used is invalid. <a href="?page=resetPasswordRequest">Request another email</a>.</div>';
        echo '</div></section>';    
        return;
    }
    
    //Make sure the user followed a valid link to get here before resetting the password
    $resetPassObj = AppigoPasswordReset::getPasswordResetForResetIdAndUserId($resetid, $userid);

    if(empty($resetPassObj))
    {
        echo '<div>The Link you used is expired. <a href="?page=resetPasswordRequest">Request another email</a>.</div>';
        echo '</div></section>';    
        return;
    }
    
    $timestamp = $resetPassObj->timestamp();
    //If the timestamp is too old (one week), expire it
    if($timestamp < (time() - 604800))
    {
        echo '<div>The Link you used is expired. <a href="?page=resetPasswordRequest">Request another email</a>.</div>';
        echo '</div></section>';    
        return;
    }
    
	echo '		<div class="credentials-section">';
	echo '			<form name="form1" method="post">';
    echo '              <input name="userid" type="hidden" id="userid" value="'.$userid.'">';
    echo '              <input name="resetid" type="hidden" id="resetid" value="'.$resetid.'">';
	echo '				<div class="input-section">';
	echo '					<label for="password">Password:</label>';
	echo '					<input name="password" type="password" id="password" onchange="validatePasswords()" class="textbox rounded-6" />';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<label for="password_2">Verify Password:</label>';
	echo '					<input name="password_2" type="password" id="password_2" onchange="validatePasswords()" class="textbox rounded-6" />';
	echo '                  <div>';
	echo '                      <div class="error-status-message" id="password_status"></div>';
	echo '                  </div>';
	echo '				</div>';
	echo '				<div class="input-section">';
	echo '					<span class="extra-links"><a href=".">Login</a>';
	echo '					</span>';
	echo '					<actionbutton onclick="submitResetPassword()" value="Reset" id="button1" class="rounded-6">Reset</actionbutton>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="status-message" id="status_message"></div>';
	echo '				</div>';
    echo '				<div>';
	echo '					<div class="error-status-message" id="error_status_message"></div>';
	echo '				</div>';
	echo '			</form>';
	echo '		</div>';
	echo '	</div>';
	echo '</section>';
    
}

?>







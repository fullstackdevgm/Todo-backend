<?php
	
	include_once('TodoOnline/base_sdk.php');
	
	$html = '';
	$html .= '<style>';
				//sign in form
	$html .= 	'*{font-size:1.3rem}';
	$html .= 	'.sign_in_form_wrap {width:90%;margin:20px auto;float:none;text-align:center}';
	$html .= 	'	#reset_password_link{margin:4px}';
	$html .= 	'	.sign_in_element .label {text-align:left;margin:0 auto 6px;width:198px}';
	$html .= 	'	.sign_in_element {display:block}';
	$html .= 	'	.sign_in.button {padding:10px 50px;margin-top:20px}';
	$html .= 	' 	.forgot_password.caption {color: gray;font-size:1.2rem}';
				//sign up form
	$html .=	' 	.sign_up_form_wrap {width:90%;height:auto;margin:30px auto;text-align:center;display:block}';
	$html .= 	'	.labeled_control > label {text-align:left;margin:0 auto 6px;width:198px}';
	$html .= 	'	.sign_up_form_wrap .labeled_control > label {margin: 0 auto 2px;width:198px}';
	$html .= 	'	.labeled_control input[type="text"]{margin:0px auto 10px;padding:4px;width:198px}';
	$html .= 	'	.labeled_control input[type="password"]{margin:0px auto 10px;padding:4px;width:198px}';
	$html .= 	'	.labeled_control input[type="email"]{margin:0px auto 10px;padding:4px;width:198px}';
	$html .= 	'	h1{font-size:1.7rem}';
	$html .=	'	.tag_line{font-size:1.4rem;line-height:1.6rem}';
	$html .= 	'	.terms_agree_statement {text-align:center;margin-bottom:20px}';
	$html .= 	'	.emailoptin{font-size:1.1rem;padding:10px 8px;display:inline-block}';
	$html .= 	'	.sign_up_title{max-width:400px;margin:0 auto 20px}';
	
				//other
	$html .= 	'	.ios_landing_content {display:block;overflow:visible;height:auto;display:block}';
	$html .= 	'	a {font-size:inherit;font-weight: bold}';
	$html .= 	'	.landing_footer{margin-top:20px}';
	
				//iphone only
	$html .=	'@media only screen 
					and (min-device-width : 320px) 
					and (max-device-width : 480px) {
					
					.terms_label{background:red;display:none !important}
				}';
	$html .= '</style>';
	
	$html .= '<div class="ios_landing_content">';
	$html .= '	<div class="ios_landing_header header">';
							//logo
	$html .= '		<a href="." style="position:relative;top:6px"><div class="app_logo sign_in_view_ios"></div></a>';
	$html .= '	</div>'; //end ios_landing_header
							//sign in 
	$html .= '   	 		<div class="sign_in_form_wrap">';
	$html .= '   	 			<div class="sign_in_element">';
	$html .= '   	 				<div class="label">' . _('Email') . '</div>';
	$html .= '   	 				<input id="username" class="email_sign_in" type="text"/>';
	$html .= '   	 				<div class="caption">&nbsp;</div>';
	$html .= '   	 			</div>';
	$html .= '   	 			<div class="sign_in_element">';
	$html .= '   	 				<div class="label">' . _('Password') . '</div>';
	$html .= '   	 				<input id="password" class="email_sign_in" type="password"/>';
	
	$html .= '   	 			</div>';
	$html .= '   	 			<div class="sign_in_element">';
	$html .= '   	 				<div class="label"></div>';
	$html .= '   	 				<div class="button sign_in" onclick="signIn()">' . _('Sign in') . '</div>';
	$html .= '						<span id="sign_in_progress" class="progress_indicator"></span>';
	$html .= '   	 				<div class="caption" style="color:red;width:100px;overflow:visible;white-space:nowrap;" id="sign_in_error_message">&nbsp;</div>';
	$html .= '   	 			</div>';
	$html .= '   	 			<div class="caption forgot_password" id="reset_password_link">' . _('Forgot your password?') . '</div>';
	$html .= '   	 		</div>';
    
	$html .= '   	 	<div class="sign_up_form_wrap">';
	$html .= '				<div class="sign_up_title">';
	$html .= '					<h1>Get a free basic account</h1>';
	$html .= '					<div class="tag_line">' . _('Try out the premium features free for 14 days').'</div>';
	$html .= '				</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label>' . _('First Name').'</label>';
	$html .= '   	 			<input type="text" id="first_name" onchange="validateFirstName()"/>';
	$html .= '					<div class="input_status" id="first_name_status"></div>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label>' . _('Last Name') . '</label>';
	$html .= '   	 			<input type="text" id="last_name" onchange="validateLastName()" />';
	$html .= '					<div class="input_status" id="last_name_status"></div>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label>' . _('Email') . '</label>';
	$html .= '   	 			<input type="email" id="email" onchange="validateEmail()" />';
	$html .= '					<div class="input_status" id="email_status"></div>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label>' . _('Password') . '</label>';
	$html .= '   	 			<input type="password" id="password_1" onchange="validatePasswords()" />';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label>' . _('Verify Password') . '</label>';
	$html .= '   	 			<input type="password" id="password_2" onchange="validatePasswords()" />';
	$html .= '					<div class="input_status" id="password_status"></div>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '   	 			<label class="terms_label"> </label>';
	$html .= '   	 			<input type="checkbox" id="emailoptin"/><span class="emailoptin"> ' . _('Receive announcement emails') . '</span>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '					<label class="terms_label"></label>';
	$html .= '   	 			<span class="terms_agree_statement">' . sprintf(_('By clicking Sign Up, you agree to our %sTerms of Service%s and that you have read and understand our %sPrivacy Policy%s'), '<a href="terms" target="_blank">', '</a>', '<a href="privacy" target="_blank">', '</a>') . '</span>';
	$html .= '   	 		</div>';
	$html .= '   	 		<div class="labeled_control">';
	$html .= '					<label></label>';
	$html .= '   	 			<div class="button" onclick="signUp()" style="padding:5px 50px;color:black">' . _('Sign Up') . '</div>';
	$html .= '					<div class="input_status" id="sign_up_status"></div>';
	$html .= '   	 		</div>';
	$html .= '   	 	</div>';
	$html .= '   	 </div>';
    
	$html .= '	<script src="' . TP_JS_PATH_LANDING_PAGE_FUNCTIONS . '"></script>';

    $html .= '<div id="landing_footer" class="landing_footer" style="padding-bottom:20px"></div>';

    $html .= '<style>body{min-width:0px;height:100%;width:100%}</style>';
    $html .= '<script>catchOrientationChange();document.getElementById(\'landing_footer\').innerHTML = getFooterLinksHtml();</script>';
    echo $html;

	
?>
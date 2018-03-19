<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<?php
	
	include_once('TodoOnline/base_sdk.php');
	include_once('TodoOnline/php/SessionHandler.php');
	include_once('TodoOnline/DBConstants.php');

	$content = '';
	
	if (isset($_GET['email']))
	{
		$emailToUnsubscribe = $_GET['email'];
		$validatedEmail = TDOMailer::validate_email($emailToUnsubscribe);
		if ($validatedEmail)
		{
			// Don't add the email address if it already exists
			if (TDOReferral::isReferralEmailAddressUnsubscribed($emailToUnsubscribe) == false)
			{
				TDOReferral::addReferralEmailUnsubscriber($emailToUnsubscribe);
			}

            $content = "<p>" . sprintf(_('Your email address (%s) has been removed from the Todo Cloud referral system.'), $emailToUnsubscribe) . "</p>";
		}
		else
		{
			// The email passed in is not a valid email address
            $content = "<p>" . _('You specified an invalid email address') . "</p>";
		}
	}
	else
	{
        $content = "<p>" . _('You did not specify an email address.') . "</p>";
	}

?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
<head>
<title id="page_title"><?php _e('Todo Cloud - Unsubscribe'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo $pathPrefix . TP_CSS_PATH_BASE; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $pathPrefix . TP_CSS_PATH_STYLE; ?>" />

<link rel="shortcut icon" href="<?php echo $pathPrefix . TP_IMG_PATH_FAV_ICON; ?>" type="image/x-icon" />

</head>
<body>

<style>html{overflow:auto}.marketing_content > * {margin-left:20px;margin-right:20px}</style>
		
		<div class="landing_header_wrap">
		   	 	<div class="landing_header">
		     		<a href="." ><div class="app_logo sign_in_view"></div></a>
		   		</div>
		</div>
		<div class="marketing_content_wrap" style="width:950px;margin:0 auto;display:block">
		    <div class="marketing_content" style="width:100%;height:auto;position:relative;top:6px;margin-top:10px">
		    	<div style="width:40%;margin:100px auto;text-align:center"><?php echo $content; ?></div>
		    </div>
		    <div class="marketing_content dropshadow left"></div>
		    <div class="marketing_content dropshadow right" style="right:-15px"></div>
		</div><br/><br/>';
		<div id="footer" class="landing_footer"></div>
</body>
</html>

<script>
	document.getElementById('footer').innerHTML = getFooterLinksHtml();

</script>
		

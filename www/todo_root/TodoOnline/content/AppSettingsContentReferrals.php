<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>

<style>
	.content_wrap {}
	.setting_options_container{padding: 30px 30px 130px;height:auto;max-width: 820px}
	.referral_section{}
	.column {display:inline-block;width: 280px;vertical-align: top;float: right}
	.column.left{width:480px;float: left;margin-right: 60px}
	.column h2 {width: 100%;font-size: 1.4rem;border-bottom: 1px solid gray;padding-bottom:8px;margin-bottom: 30px}
	.column h3 {margin-bottom: 0;padding-left: 20px}
	.column .referral_box {background-color: white;border: 1px solid lightgray;margin: 8px auto 40px;width: 460px}
	.referral_box h4 {padding-left: 20px;margin-bottom: 0}
	.referral_box .social_links{margin:20px;text-align: right}
	.referral_box_share textarea {border: 1px solid lightgray;display: block;height: 50px;margin: 20px auto;padding: 10px 0 0 10px;width: 408px;color:gray}
	
	.referral_box_share.email textarea {height: 234px}
	.referral_box_share.friend_email textarea {margin-top: 6px}
	.email_button .button { background-image: url(https://s3.amazonaws.com/todopro.com/images/home/Sign-Up-Button-Middle@2x.png);background-position: 0 -40px;color: rgb(240, 240, 240);margin: 0 10px 20px 20px;padding: 6px 0;width: 120px}

    .email_button .button.disabled {
        background-image: none;
        color: #555;
    }
	.email_button .button:hover {background-position: 0 -4px}
	.referral_section.details ul {list-style-type: disc;margin-left: 20px;max-width: 460px}
	.referral_section.details ul li {margin-bottom: 10px}
	
	.twitter-share-button {position: relative;top:0px}
	.facebook_share_button {background-image: url(<?php echo TP_IMG_FB_SHARE_BUTTON; ?>);display: inline-block;height: 28px;width: 75px}
	.email_button {text-align: right;padding-right: 10px}
	
	.showcase {background-color:white;border-radius:6px;border:1px solid gray;background-image: url(<?php echo TP_IMG_REFERRAL_SHOWCASE; ?>);min-height: 324px;max-width:820px;margin:10px 0 70px 0;background-repeat: no-repeat;background-size: contain;background-position: 100% 100%;}
	.showcase .title {margin: 30px 40px 40px 40px}
	.showcase .title {max-width: 340px}
	.showcase .title p {font-size: 2.8rem;font-weight: bold;line-height: 3.2rem;margin: 0;text-shadow: -1px 0 rgba(255,255,255,0.5), 0 1px rgba(255,255,255,0.5), 1px 0 rgba(255,255,255,0.5), 0 -1px rgba(255,255,255,0.5);}
    .showcase .details {font-size: 1.5rem;line-height: 2rem;margin-left: 40px;margin-right: 40px;max-width: 320px;text-shadow: -1px 0 #fff, 0 1px #fff, 1px 0 #fff, 0 -1px #fff;}
    
    .success_msg {display: none}
	@media only screen and (-webkit-min-device-pixel-ratio: 2), only screen and (min-device-pixel-ratio: 2) {
		.facebook_share_button {background-image: url(<?php echo TP_IMG_FB_SHARE_BUTTON_2X; ?>)}
		.showcase {background-image: url(<?php echo TP_IMG_REFERRAL_SHOWCASE_2X; ?>);background-size: 464px 324px}
	}
    @media only screen and (max-width : 1110px) {
        .column.right {
            width: 100%;
            margin-bottom: 40px;
            display: block;
            float: none;
        }
        .column.left{
            width: 100%;
            margin: 0;
            display: block;
            float: none;
        }
        .column.left .referral_box{
            width: 100%;
        }
        .column.left .referral_box .referral_box_share{
            width: 92%;
            padding: 4%;
        }
        .column.left .referral_box .referral_box_share textarea{
            width: 100%;
            margin: 0;
        }
        .column.left .referral_section.details ul{
            max-width: 100%;
        }

    }
    @media only screen and (max-width : 768px) {
        .showcase .title p {font-size: 1.8rem;line-height: 2.8rem}
    }

	
</style>

<div class="setting_options_container">

<?php

$userId = $session->getUserId();
$isAutorenewingIAPUser = TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userId);

if ($isAutorenewingIAPUser)
{
?>

	<div class="column left">
		<h2><?php _e('Referrals are not available for your account'); ?></h2>
		<div class="referral_section">
			<h3><?php _e('How can I enable my account for referrals?'); ?></h3>
			<div class="referral_box">
				<div class="referral_box_share">
					<p style="margin:24px;"><?php _e('Your account is currently being paid for with an auto-renewing in-app purchases (purchases made from your iOS device). The auto-renewing in-app purchase system is not compatible with the referral program.'); ?></p>
					<p style="margin:24px;"><?php _e('If you would like to become eligible to participate in the referral program, you will need to prevent the in-app purchase auto-renewing mechanism from running and wait until your current premium account reaches its expiration date.'); ?></p>
                    <p style="margin:24px;"><?php printf(_('For more information, please visit the %sAppigo Help Center%s.'), '<a href="http://help.appigo.com/entries/23360366-Why-is-my-account-not-eligible-to-participate-in-the-Todo-Pro-Referrals-Program-" target="_blank" style="cursor:hand;text-decoration:underline;">', '</a>'); ?></p>
				</div>
			</div>
		</div>
	</div>

<?php
}
else
{
?>

	<div class="showcase">
		<div class="title">
			<p><?php _e('Invite your friends,'); ?></p>
			<p><?php _e('and earn up to'); ?></p>
			<p><?php _e('1 year free!'); ?></p>
		</div>
		<div class="details">
			<?php _e('Your friends will get one month free, and you&#39;ll get an extra month, when they purchase a premium account'); ?>
		</div>		
	</div>

<?php
	
	$referralInfo = NULL;
    
	$referralInfo = TDOReferral::accountExtensionInfoForUserId($userId);
	
	$displayName = TDOUser::displayNameForUserId($userId);
	
	$referralCode = $referralInfo['referral_code'];
	$eligibleMonths = $referralInfo['eligible_extensions'];
    $totalExtensions = $referralInfo['extensions_received'];
	$totalReferrals = $referralInfo['total_referrals'];
	$expirationTimestamp = $referralInfo['expiration_date'];
	
	$referralURL = "https://www.todo-cloud.com/?referralcode=" . $referralCode;
	
    ?>
    <div class="column right">
        <h2><?php _e('Referrals Summary'); ?></h2>
        <div class="referral_section summary">
    <?php
    if($eligibleMonths > 0)
    {
        $eligiblesuff = '';
        if($eligibleMonths > 1 && DEFAULT_LOCALE_IN_USE =='en_US') {
            $eligiblesuff = 's';
        }
        ?>
        <p><strong><?php printf(_('You&#39;re eligible to earn %s free month%s through referrals.'), $eligibleMonths, $eligiblesuff); ?></strong></p>
       <?php
    }
    else if(isset($referralInfo['eligible_extension_date']))
    {
        $extensionDate = TDOUtil::taskDueDateStringFromTimestamp($referralInfo['eligible_extension_date']);
       ?>
        <p><strong><?php printf(_('You&#39;ve earned a full year through referrals! You&#39;ll be eligible to earn another free month on %s.'), $extensionDate); ?></strong></p>
        <?php
    }
    ?>
   
    <p><strong><?php _e('People who have signed up via your link:'); ?></strong> <?php echo $totalReferrals; ?></p>
    <p><strong><?php _e('Free months earned from paid referrals:'); ?></strong> <?php echo $totalExtensions; ?> (<?php _e('Remember, you earn free time when referrals purchase a premium account'); ?>)</p>
    <p><strong><?php _e('Account Expiration:'); ?></strong> <?php echo TDOUtil::taskDueDateStringFromTimestamp($expirationTimestamp); ?></p>
    
        </div>
    </div>


	<div class="column left">
		<h2><?php _e('Share your referral link with friends'); ?></h2>
		<div class="referral_section">
			<h3><?php _e('On Facebook or Twitter'); ?></h3>
			<div class="referral_box">
				
				<div class="referral_box_share">
					<textarea readonly="readonly" name="share_text" id="share_text"><?php _e('I&#39;m keeping track of everything with Todo Cloud. Give it a try &amp; get the premium features free for one month!'); echo $referralURL; ?></textarea>
				</div>
                <?php if(DEFAULT_LOCALE_IN_USE !=='zh_CN'):?>
				<div class="social_links">
					<?php $fb_share_link = 'href="https://www.facebook.com/dialog/feed/?app_id=138094306353396&redirect_uri=http%3A%2F%2Fwww.facebook.com&link=https://www.todo-cloud.com/?referralcode='.$referralCode.'&picture=http://www.appigo.com/wp-content/uploads/2016/06/logo-196.png&caption=I\'m%20keeping%20track%20of%20everything%20with%20Todo%20Pro.%20Give%20it%20a%20try%20and%20get%20the%20premium%20features%20free%20for%20one%20month!"'; ?>
					<a target="_blank" <?php echo $fb_share_link; ?> class="facebook_share_button" title="<?php _e('Share your referral link on Facebook'); ?>"></a>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://www.todo-cloud.com/?referralcode=<?php echo $referralCode ?>" data-text="<?php _e('I\'m keeping track of everything with Todo Cloud. Give it a try &amp; get the premium features free for one month!'); ?>" data-size="large" data-count="none"></a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</div>
                <?php endif; ?>
			</div>
		
			<h3><?php _e('Or Send an Email'); ?></h3>
			<div class="referral_box">
				
				<div class="referral_box_share email">
					  <textarea readonly="readonly" name="email_text" id="email_text"><?php
                          printf(_("Hello,\n\n%s wants you to try out Todo Cloud. Todo Cloud is an amazing to-do service that will help you be more productive. Todo Cloud can handle all your personal projects and shared projects. You can easily organize, plan, and collaborate with others.\n\nA Todo Cloud premium account is $19.99/year. You can also download the free iPhone/iPad, Mac, and Android apps to keep your tasks updated anywhere you are.\n\nIf you sign up now using %s&#39;s referral link below, we&#39;ll give you a Todo Cloud premium account to try out for a full month! Signing up is easy and doesn&#39;t require a credit card.\n\n%s"), $displayName, $displayName, $referralURL);
                          ?></textarea>
				</div>
				<h4><?php _e('Type your friend&#39;s email here:'); ?></h4>
				<div class="referral_box_share friend_email">
					<textarea id="referralEmails" onchange="sendReferralEmailsEnableButton(this)" oninput="sendReferralEmailsEnableButton(this)" name="friend_emails" id="friend_emails" placeholder="<?php _e('Email address(es) -- use a comma to separate.'); ?>" ></textarea>
				</div>
				<div class="email_button" id="emailButtonWrap">
					<span id="success_msg" class="success_msg"><?php _e('Success! Your referral links were sent.'); ?></span>
					<input type="submit" class="button disabled" value="<?php _e('Send'); ?>" onclick="sendReferralEmails()" id="sendReferralsEmailButton" title="<?php _e('Send your referral link to your friends'); ?>" onmouseout="hideSuccesMsg()"/></input>
					<input type="hidden" id="referralUrl" value="<?php echo $referralURL ?>" />
				</div>
			</div>
		</div>
												  
		<h2><?php _e('Program Details'); ?></h2>
		<div class="referral_section details">
			<ul>
				<li><?php _e('Earn up to 12 months of a free premium account per year for your referrals – your premium account can be extended up to 12 months within any rolling 12-month period'); ?></li>
				<li><?php _e('Each time someone purchases a premium account using your referral link, you&#39;ll get one month added to your premium account (up to 12 months in the rolling 12-month period)'); ?></li>
				<li><?php _e('New accounts are eligible to receive 2 additional free weeks when using your referral link (2 weeks added to the standard 2 week trial period – one month total)'); ?></li>
				<li><?php _e('Refer more than 12 friends in one year and additional friends will still be eligible for their 2 free weeks'); ?></li>
				<li><?php _e('Your premium account will be extended one month when new account holders successfully complete the sign up process using your referral link and purchase a premium account'); ?></li>
				<li><?php _e('Offer cannot be combined with other promotions, including any other free trial offers'); ?></li>
				<li><?php _e('Referrals sent via email will include your name and/or email address so your friends will know the message is from you'); ?></li>
				<li><?php _e('Appigo reserves the right to amend, modify, or waive these Program Details from time to time in its sole discretion'); ?></li>
			</ul>
		</div>
	</div>
</div>

<?php
}
?>
												  
<script>

//On page load, clear the new feature flag for viewing the referrals
clearNewFeatureFlagIfNeeded(<?php echo NEW_FEATURE_FLAG_REFERRALS;?>, "new_feature_flag_referrals");

function hideSuccesMsg()
{
	document.getElementById('success_msg').setAttribute('style', '');
};
function sendReferralEmailsEnableButton(el) {
    if (el.value == '') {
        document.getElementById('sendReferralsEmailButton').setAttribute('class', 'button disabled');
        return false;
    }
    if (el.value.length > 1) {
        var emails = el.value.split(',')
        if (isValidEmailAddress(emails[0])) {
            document.getElementById('sendReferralsEmailButton').setAttribute('class', 'button');
            return true;
        }
    }
    document.getElementById('sendReferralsEmailButton').setAttribute('class', 'button disabled');

    return false;
}
function sendReferralEmails()
{
	var doc = document;
	var emails = doc.getElementById('referralEmails').value;
	
	//disable send button
	doc.getElementById('sendReferralsEmailButton').setAttribute('onclick', '');
	doc.getElementById('sendReferralsEmailButton').setAttribute('class', 'button disabled');
	doc.getElementById('success_msg').setAttribute('style', '');
	
	if (emails.length > 0 && emails.indexOf('@') > -1) //make sure there is a least one email address
	{
		var ajaxRequest = getAjaxRequest();  // The variable that makes Ajax possible!
		if(!ajaxRequest)
			return false;
		
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function()
		{
			if(ajaxRequest.readyState == 4)
			{
	            try 
	            {	
		            var response = JSON.parse(ajaxRequest.responseText);
	        
	                if(response.success)
	                {
	                	doc.getElementById('success_msg').setAttribute('style', 'display:inline-block');
	                	//enable send button
	                	doc.getElementById('sendReferralsEmailButton').setAttribute('onclick', 'sendReferralEmails()');
	                	doc.getElementById('sendReferralsEmailButton').setAttribute('class', 'button');
	                }
	                else
	                {
		                displayGlobalErrorMessage("<?php _e('Unable to send referral links'); ?>");
	                }
	            }
	            catch(e)
	            {
	                displayGlobalErrorMessage("<?php _e('Unknown response from server:'); ?> " + e);
	            }
			}
		}
		
		var params = 'method=sendReferralEmail&emails=' + emails + '&link=' + encodeURIComponent(doc.getElementById('referralUrl').value);
		ajaxRequest.open("POST", "." , false);
		//Send the proper header information along with the request
		ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxRequest.send(params);	
	}
};
</script>

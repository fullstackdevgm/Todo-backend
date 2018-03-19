#!/usr/bin/php -q
<?php

// Include Classes
include_once('TodoOnline/base_sdk.php');

// These will get Boyd's user on Plano.
//	define('BEGIN_TIMESTAMP', 1346364646);
//	define('END_TIMESTAMP', 1346364648);

// These are the production timestamps which represent 5,731 users
	define('BEGIN_TIMESTAMP', 1351598302);
	define('END_TIMESTAMP', 1351723708);
	define('EXTENSION_INTERVAL', 'P14D');
	define('PAUSE_EMAILS_EVERY_NUM', 50);
	
	$bounceEmails = array(
						  "bmonteith1@comcast.net",
						  "kurt.essler@s-itsolutions.at",
						  "iiic119@bellsouth.net",
						  "muessig@neotokyo.de",
						  "mosbornejt@gmail.com",
						  "todo@pills2u.co.uk",
						  "madalex69@web.de",
						  "frank@summerknights.de",
						  "Melgoza@me.com",
						  "danielzambonini@aol.com",
						  "schiffmann@nrw-online.de",
						  "doe@hotmail.com",
						  "zss.gtdlife@mail.com",
						  "brylske@aol.com",
						  "amll@gmail.com",
						  "nikolay.denisov@icloud.com",
						  "scandco@m3.com",
						  "fedenew@virgilio.it",
						  "max@cavs-samara.ru",
						  "peter.novak@gmx.at",
						  "warren.lippert@sierrawes.com",
						  "srinhart@gmail.com",
						  "lesmzekb50@gmail.com",
						  "jd54@orange.fr",
						  "jhalebian@lshllp.com",
						  "thierry.fillait.travail@gmail.com",
						  "ajj@gmail.com",
						  "startska@kc4.so-net.ne.jp",
						  "info@bnvied.com",
						  "guillaume.graindor@me.com",
						  "arramosabogadod@gmail.com",
						  "mikerosi11@gamil.com",
						  "kirkegaard@rigers.com",
						  "vgrove48@mac.com",
						  "cardinye@gmail.com",
						  "cheng.keywow@gnail.com",
						  "vgrove55125@yahoo.com",
						  "Adman@pcmc.sa",
						  "leo1002@qq.com",
						  "john@example.com",
						  "jen.vencia@yahoo.com",
						  "stevegonzales@icloud.net",
						  "lcommins@gmaiil.com",
						  "markus.jaeke@web.de",
						  "www.karl-heinz.preusser@boschrexroth.de",
						  "martin@mstech.com.au",
						  "yshi@mogi-shoko.co.jp",
						  "dsmdsm@nm.ru",
						  "claire.gasgoine@ciras.org.uk",
						  "olivier@yahoo.fr",
						  "stee.quandt@grahampackaging.com",
						  "179934623@126.com",
						  "djsasmel@gmail.com",
						  "marinercheer@yahho.com",
						  "j.howisey@comcast.net",
						  "maailtocolin@gmail.com",
						  "kxc4@gmail.com",
						  "philipp_jenny@hotmail.ch",
						  "985979883@mail.ru",
						  "sam.eer@sameerhalai.com",
						  "poul.jensem@mail.dk",
						  "gery.wolff@mac.com",
						  "christianwinzenburg@web.de",
						  "bram.vandnebogaerde@gmail.com",
						  "teamkleiva@jornnystad.no",
						  "klaus.kowalczyk@agfa.com",
						  "christinacaddy@hotmsil.com",
						  "Ning.2137@hotmail.com",
						  "ogata1@aiasso.velvet.jp",
						  "1119266184@qq.com",
						  "khernandez@supertec.com",
						  "gkennedy@mciu.org",
						  "ingenieria@sonae.mx",
						  "dupp@wienerwald.de",
						  "waterd@gaisford.com",
						  "stuart.turner@nexteer.com",
						  "putnick@example.com",
						  "arsenigomezl@gmail.com",
						  "conract@szczerbik.net",
						  "ndanahaer@valspar.com",
						  "rynamek@yahoo.com",
						  "ajlecona@yah.com",
						  "graeme.mils@gmail.com",
						  "maurocostanzo@hotmiail.com",
						  "wangjia820919@example.com",
						  "philipnutall@sky.com",
						  "javi.lechon8248@gmil.com",
						  "info@berndschaefer.org",
						  "jklfjdlfljlJ@llds.com",
						  "a.m@q.de",
						  "vikkur@example.com",
						  "fc@ccc.cg",
						  "daniele.caponnetto@gnail.com",
						  "maxime.1705@gmail.fr",
						  "ghomas.ostermeyer@web.de",
						  "amk@dramashop.eu",
						  "bjwals000@gmail.com",
						  "af@af.com",
						  "diplomatictours@tahoo.com",
						  "beckersjoeri@telenet.com",
						  "justi.s@interia.pl",
						  "gustavopablo@yahoo.es",
						  "mediza40@gmail.com",
						  "guatavoromero@mac.com",
						  "g@kakazu.com",
						  "proffumi@gmail.com",
						  "mhforrmanmd@yahoo.com",
						  "bj@eaststarentertainment.com.com",
						  "AYHAN@nikken.com.tr",
						  "scottdye@ingramcontent.com",
						  "k.effenn@metrohm.de",
						  "mdillard67@panaceainc.net",
						  "claircorke@hotmail.co.uk",
						  "ciara217@gmail.com",
						  "soulfuricrecordings@gmail.com",
						  "lleonp@me.com",
						  "v.a.podkuyko@gmail.ru",
						  "e.ott@mac.com",
						  "todopro999@gmail.com",
						  "ttproschek@jobco.de",
						  "gmyotte@me.com",
						  "tokiyakunn@i.softbank.jp",
						  "a@aaaa.lt",
						  "rick_transportatinny@yahoo.com",
						  "drtabernero@hotmail.es",
						  "Dalay24@yahoo.com",
						  "spalomares@palomares.adv.br",
						  "etienne.bouron@gmail.fr",
						  "aharatph@gmail.com",
						  "jasony@marshillfairfield.com",
						  "james@abc.com",
						  "user@dddm.com",
						  "mammothtechnology@yahoo.com",
						  "kellyyyoung_6@hotmail.com",
						  "pierre.avp.thomas@gmaim.com",
						  "Deniz_style@web.de",
						  "senaiti@ig.com.br",
						  "desei09@googlelmail.com",
						  "aposadz@gmail.com",
						  "od2201@gmail.com",
						  "jameshutsell74@yahoo.com.com",
						  "falko.fechy@t-online.de",
						  "derekmtz07@hotmail.com"
	);

	function sendEmailToUser($username, $firstName, $newExpirationDate)
	{
		$newExpirationDateString = $newExpirationDate->format('F j, Y');
		
		$subject = "Thank you for joining us on Todo Cloud";
		
		$htmlBody = "<p>$firstName,</p>\n";
		$textBody = "$firstName,\n\n";
		
		$htmlBody .= "<p>Thank you for signing up for a Todo Cloud account. We want to sincerely apologize if you experienced any service interruptions during the first day after the launch of the service. Even after all of our extensive testing, our service was overloaded by the large number of people joining the new service.</p>\n";
		$textBody .= "Thank you for signing up for a Todo Cloud account. We want to sincerely apologize if you experienced any service interruptions during the first day after the launch of the service. Even after all of our extensive testing, our service was overloaded by the large number of people joining the new service.\n\n";
		
		$htmlBody .= "<p>Fortunately, we have resolved these issues and the Todo Cloud service is healthy and working well even with many new users signing up. If you are still experiencing any issue with your account, please do not hesitate to contact our support team at support@appigo.com. We have all been working harder than ever to respond and help everyone make the transition.</p>\n";
		$textBody .= "Fortunately, we have resolved these issues and the Todo Cloud service is healthy and working well even with many new users signing up. If you are still experiencing any issue with your account, please do not hesitate to contact our support team at support@appigo.com. We have all been working harder than ever to respond and help everyone make the transition.\n\n";
		
		$htmlBody .= "<h2>We've Extended Your Account</h2>\n";
		$textBody .= "We've Extended Your Account\n";
		$textBody .= "===========================\n\n";
		
		$htmlBody .= "<p>We have extended your premium account by another fourteen days. We know this may not make up for everything that happened, but now that the service is working well, we hope you thoroughly enjoy it. Your premium account is now valid through:</p>\n";
		$htmlBody .= "<center><h2>$newExpirationDateString</h2></center>\n";
		$textBody .= "We have extended your premium account by another fourteen days. We know this may not make up for everything that happened, but now that the service is working well, we hope you thoroughly enjoy it. Your premium account is now valid through:\n\n";
		$textBody .= "		$newExpirationDateString.\n\n";
		
		$htmlBody .= "<hr/>\n";
		
		$htmlBody .= "<h2>Issues You May Have Experienced</h2>\n";
		$textBody .= "Issues You May Have Experienced\n";
		$textBody .= "===============================\n\n";
		
		$htmlBody .= "<h3>Missing or incomplete notes?</h3>\n";
		$textBody .= "Missing or incomplete notes?\n";
		
		$htmlBody .= "<p>Some people have mentioned that when they migrated their account from Todo Online, some of their task notes, contexts, and tags were missing or incomplete. We have a solution to fix this problem and restore your notes. If you were affected by this, please contact us (support@appigo.com) and we will send you further instructions.</p>\n";
		$textBody .= "Some people have mentioned that when they migrated their account from Todo Online, some of their task notes, contexts, and tags were missing or incomplete. We have a solution to fix this problem and restore your notes. If you were affected by this, please contact us (support@appigo.com) and we will send you further instructions.\n\n";
		
		$htmlBody .= "<h3>Premium account expiration date not valid?</h3>\n";
		$textBody .= "Premium account expiration date not valid?\n";
		
		$htmlBody .= "<p>Purchases of a Todo Cloud premium account made from and iPhone, iPad, or iPod touch on the first day of the product launch may not have received the proper credit on their <a href=\"https://www.todo-cloud.com/?appSettings=show&option=subscription\" target=\"_blank\">premium account</a> (view your premium account online at www.todo-cloud.com Settings > Premium Account).</p>\n";
		$textBody .= "Purchases of a Todo Cloud premium account made from and iPhone, iPad, or iPod touch on the first day of the product launch may not have received the proper credit on their <a href=\"https://www.todo-cloud.com/?appSettings=show&option=subscription\" target=\"_blank\">premium account</a> (view your premium account online at www.todo-cloud.com Settings > Premium Account).\n\n";
		
		$htmlBody .= "<p>Now that Todo Cloud is working well, this should no longer happen. We have also added additional mechanisms into Todo Cloud that will be available in the next update of Todo Cloud for iOS to prevent this from happening.</p>\n";
		$textBody .= "Now that Todo Cloud is working well, this should no longer happen. We have also added additional mechanisms into Todo Cloud that will be available in the next update of Todo Cloud for iOS to prevent this from happening.\n\n";
		
		$htmlBody .= "<p>If you were affected by this, please follow the following steps:</p>\n";
		$textBody .= "If you were affected by this, please follow the following steps:\n\n";
		
		$htmlBody .= "<ol>\n";
		$htmlBody .= "<li><strong>Exit Todo/Todo Cloud.</strong></li>\n";
		$htmlBody .= "<li><strong>Restart Todo/Todo Cloud.</strong> Double-tap your iPhone/iPad/iPod touch's home button for the running apps to show. Tap and hold the Todo/Todo Cloud app icon (the one that is running) until an 'x' button appears. tap and hold the app icon until a red \"-\" button appears. Tap the red '-' button to stop Todo/Todo Cloud from running. Open the Todo/Todo Cloud app from your device's home screen.</li>\n";
		$htmlBody .= "<li><strong>Verify your premium account date.</strong> After launching Todo/Todo Cloud, visit your account settings screen by going into Settings -> Account (on Todo Cloud) or Settings -> Synchronization -> Service (on Todo). Visiting this screen should communicate automatically with the Todo Cloud service and apply your In-App Purchase.</li>\n";
		$htmlBody .= "</ol>\n";
		
		$textBody .= "    1. Exit Todo/Todo Cloud.\n\n";
		$textBody .= "    2. Restart Todo/Todo Cloud. Double-tap your iPhone/iPad/iPod touch's home button for the running apps to show. Tap and hold the Todo/Todo Cloud app icon (the one that is running) until an 'x' button appears. tap and hold the app icon until a red \"-\" button appears. Tap the red '-' button to stop Todo/Todo Cloud from running. Open the Todo/Todo Cloud app from your device's home screen.\n\n";
		$textBody .= "    3. Verify your premium account date. After launching Todo/Todo Cloud, visit your account settings screen by going into Settings -> Account (on Todo Cloud) or Settings -> Synchronization -> Service (on Todo). Visiting this screen should communicate automatically with the Todo Cloud service and apply your In-App Purchase.\n\n";

		$htmlBody .= "<p>If, after following these steps, your premium account does not properly reflect the In-App Purchase you made, please forward the receipt you received from Apple to us (support@appigo.com) so we can work with you to fix it on our side.</p>\n";
		$textBody .= "If, after following these steps, your premium account does not properly reflect the In-App Purchase you made, please forward the receipt you received from Apple to us (support@appigo.com) so we can work with you to fix it on our side.\n\n";
		
		$htmlBody .= "<hr/>\n";
		
		$htmlBody .= "<h2>Thank You</h2>\n";
		$textBody .= "Thank You\n";
		$textBody .= "=========\n\n";
		
		$htmlBody .= "<p>Again, we apologize for the service interruption and want to thank you for sticking with us. We are highly motivated to keep improving Todo Cloud and we welcome your feedback.</p>\n";
		$textBody .= "Again, we apologize for the service interruption and want to thank you for sticking with us. We are highly motivated to keep improving Todo Cloud and we welcome your feedback.";
		
		$htmlBody .= "<p><strong>The Appigo Team</strong></p>\n";
		$textBody .= "The Appigo Team\n";
		
		return TDOMailer::sendHTMLAndTextEmail($username, $subject, EMAIL_FROM_NAME, EMAIL_FROM_ADDR, $htmlBody, $textBody);
	}
	
	// Get an array of user IDs who created accounts between the time that we
	// launched and the time that we made the service function optimally.
	$earlyAdopterIDs = TDOUser::getUserIDsWithDateRange(BEGIN_TIMESTAMP, END_TIMESTAMP);
	
	$numOfEarlyAdopters = count($earlyAdopterIDs);
	echo "Number of Early Adopters: $numOfEarlyAdopters\n";
	echo "==============================\n";
	
	$processedCount = 0;
	$emailsSent = 0;
	$bouncedEmails = 0;
	
	foreach($earlyAdopterIDs as $userid)
	{
		$processedCount++;
		
		$email = TDOUser::usernameForUserId($userid);
		$firstName = TDOUser::firstNameForUserId($userid);
		
		// Read the user's current expiration date and add on an extra month
		$subscription = TDOSubscription::getSubscriptionForUserID($userid);
		
		if (empty($email))
		{
			echo "No email for userid: $userid\n";
			continue;
		}
		
		if (empty($subscription))
		{
			echo "No subscription found for userid ($email): $userid\n";
			
			$temporaryExpirationTimestamp = time();
			
            $subscriptionID = TDOSubscription::createSubscription($userid, $temporaryExpirationTimestamp, SUBSCRIPTION_TYPE_UNKNOWN, SUBSCRIPTION_LEVEL_TRIAL);
            if($subscriptionID == false)
            {
				echo "Unable to create subscription for $email\n";
				continue;
            }
			
			$subscription = TDOSubscription::getSubscriptionForSubscriptionID($subscriptionID);
			if (empty($subscription))
			{
				echo "Unable to find the subscription, despite all of our incredible trying for $email\n";
				continue;
			}
			
			echo "**** Calvin was a genius, we just fixed a missing subscription record for $email\n";
		}
		
        if(TDOInAppPurchase::userHasNonCanceledAutoRenewingIAP($userid) == true)
        {
            echo "Failed to extend subscription because user has autorenewing IAP set up";
            continue;
        }
        
		// Determine a new expiration date
		$expirationTimestamp = $subscription->getExpirationDate();
		$expirationDate = new DateTime('@' . $expirationTimestamp);
		$newExpirationDate = $expirationDate->add(new DateInterval(EXTENSION_INTERVAL));
		$newExpirationTimestamp = $newExpirationDate->format('U');
		
		// Update the user's subscription
		$subscriptionID = $subscription->getSubscriptionID();
		$subscriptionType = $subscription->getSubscriptionType();
		$subscriptionLevel = $subscription->getSubscriptionLevel();
		
		echo "Updating $email account with new expiration date of " . $newExpirationDate->format('Y-m-d') . "\n";
		
		if (TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $newExpirationTimestamp, $subscriptionType, $subscriptionLevel) == false)
		{
			echo "Failed to extend a user's subscription (userid: $userid, username: $email)";
			continue;
		}
		
		// Log this as an admin action on the user's account
		$changeDescription = "New Expiration Date: " . date("D d M Y", $newExpirationTimestamp) . ", Early Adopter Email";
		TDOUser::logUserAccountAction($userid, $userid, USER_ACCOUNT_LOG_TYPE_EXP_DATE, $changeDescription);
		
		// Do NOT send email to bounced emails
		if (in_array($email, $GLOBALS['bounceEmails']) == true)
		{
			echo "**** NOT sending email to BOUNCED address: $email\n";
			$bouncedEmails++;
			continue;
		}
		
		echo "Sending email to $email\n";
		
		if ( ($emailsSent % PAUSE_EMAILS_EVERY_NUM) == 0)
		{
			// Sleep for one second to not exceed the rate limit of Amazon SES.
			sleep(1);
		}
		
		if (sendEmailToUser($email, $firstName, $newExpirationDate) == false)
		{
			echo "Failed to send email to $email (userid: $userid)\n";
			continue;
		}
		
		$emailsSent++;
	}
	
	echo "==========================\n";
	echo "$processedCount - accounts processed\n";
	echo "$emailsSent - emails sent\n";
	echo "$bouncedEmails - bounced emails skipped\n"

?>

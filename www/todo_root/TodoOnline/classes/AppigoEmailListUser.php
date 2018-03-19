<?php
//      AppigoEmailListUser
//      Used to handle all user data

// include files
include_once('AWS/sdk.class.php');
include_once('TodoOnline/base_sdk.php');
include_once('Facebook/config.php');
include_once('Facebook/facebook.php');
include_once('TodoOnline/DBConstants.php');

define ('APPIGO_EMAIL_LENGTH', 100);

define ('EMAIL_CHANGE_SOURCE_UNKNOWN', 0);
define ('EMAIL_CHANGE_SOURCE_TODO_CLOUD', 1);
define ('EMAIL_CHANGE_SOURCE_SUPPORT_ACCOUNT', 2);
define ('EMAIL_CHANGE_SOURCE_PRODUCT_REGISTRATION', 3);
define ('EMAIL_CHANGE_SOURCE_WEB_PAGE', 4);
define ('EMAIL_CHANGE_SOURCE_MAIL_SERVICE', 5);

class AppigoEmailListUser extends TDODBObject
{
    public function __construct()
    {
        parent::__construct();
        $this->set_to_default();
    }
    
    public function set_to_default()
    {
        parent::set_to_default();
    }

	public static function deleteListUser($email)
	{
        if(!isset($email))
            return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		$email = mysql_real_escape_string($email, $link);
		$sql = "DELETE FROM appigo_email_list_user WHERE email='$email'";
		if(mysql_query($sql, $link))
		{
			TDOUtil::closeDBLink($link);

			return true;
		}
		else
		{
			error_log("Unable to delete user $email");
		}

        TDOUtil::closeDBLink($link);
        return false;
	}
    
	public static function existsEmail($email) 
	{
        if(empty($email))
            return false;

        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
           return false;
        }

		$email = mysql_real_escape_string($email, $link);
		$sql = "SELECT COUNT(*) FROM appigo_email_list_user WHERE email='$email'";
		$response = mysql_query($sql, $link);
		if($response)
		{
			$total = mysql_fetch_array($response);
			if($total && isset($total[0]) && $total[0] == 1)
			{
				TDOUtil::closeDBLink($link);
				return true;
			}
		}
		else
		{
			error_log("Unable to get count of users");
		}

        TDOUtil::closeDBLink($link);
        return false;

	}
    
	public static function updateListUser($email, $source = EMAIL_CHANGE_SOURCE_UNKNOWN, $link = NULL)
	{
		if($email == NULL)
        {
            error_log("AppigoEmailListUser::addListUser failed with no email");
			return false;
		}

        if($link == NULL)
        {
            $closeLink = true;
            $link = TDOUtil::getDBLink();
            if(!$link)
            {
                error_log("AppigoEmailListUser::updateListUser() could not get DB connection.");
                return false;
            }
        }
        else
            $closeLink = false;	
        

		$email = mysql_real_escape_string($email, $link);
        $email = strtolower($email);
		$changeTimestamp = time();

		if(AppigoEmailListUser::existsEmail($email) == true)
            $sql = "UPDATE appigo_email_list_user SET last_source=$source, timestamp=$changeTimestamp WHERE email='$email'";
        else
            $sql = "INSERT INTO appigo_email_list_user (email, last_source, timestamp) VALUES ('$email', $source, $changeTimestamp)";

        $response = mysql_query($sql, $link);
        if($response)
        {
            if($closeLink)
                TDOUtil::closeDBLink($link);
            return true;
        }

        if($closeLink)
            TDOUtil::closeDBLink($link);

        return false;
	}
    
    
    public static function sendMailingListSignupEmail($email, $emailVerifyURL)
    {
        // If the user has a valid subscription, generate a task creation email and send it to them
        $taskCreationEmail = NULL;
        
        $subject = _('Appigo Announcement Email List');

        $textBody = _('Hello') . $email . ",\n\n";
        $htmlBody = "<p>" . _('Hello') . $email . " ,</p>\n";
        
        $textBody .= _('Thank you for signing up for our product announcement email list.');
        $htmlBody .= '<p>' . _('Thank you for signing up for our product announcement email list.') . "</p>\n";
        
        if(!empty($emailVerifyURL))
        {
            $textBody .= _('Please complete your sign up for the list by clicking the link below to verify your email address') . ".\n" . $emailVerifyURL;
            $htmlBody .= '<p>' . sprintf(_('Please complete your sign up by %sclicking here%s to verify your email address.'), '<a href="' . $emailVerifyURL . '">', '</a>') . "</p>\n";
        }
        else
            error_log("Email verify URL was empty for user: " . $email);

        $textBody .= "\n\n" . _('Thank you from the Appigo Team') . "\n";
        $htmlBody .= "<p>" . _('Thank you from the Appigo Team') . "</p>\n";
        
        TDOMailer::sendAppigoHTMLAndTextEmail($email, $subject, SUPPORT_EMAIL_FROM_NAME, SUPPORT_EMAIL_FROM_ADDR, $htmlBody, $textBody);        
        
    }    
    

}


<?php
    define('FRESHDESK_SHARED_SECRET','74401b1ae548f7b0d1a50ed9b4ac6517');
    define('FRESHDESK_BASE_URL','http://support.appigo.com/');	//With Trailing slashes

// return $base . "login/sso/?name=" . urlencode($name) . "&email=" . urlencode($email) . "&hash=" . hash('md5', $name . $email . $secret);

    // FreshDesk Methods
    function getSSOUrl($strName, $strEmail)
    {
        $timestamp = time();
        $to_be_hashed = $strName . FRESHDESK_SHARED_SECRET . $strEmail . $timestamp;
        $hash = hash_hmac('md5', $to_be_hashed, FRESHDESK_SHARED_SECRET);

        return FRESHDESK_BASE_URL."login/sso/?name=".urlencode($strName)."&email=".urlencode($strEmail)."&timestamp=".$timestamp."&hash=".$hash;
    }

    function sso_login($username, $password)
    {
        $result = array();

        $user = TDOUser::getUserForUsername($username);
        if($user == false)
        {
            $user == AppigoUser::getUserForUsername($username);
            if($user == false)
            {
                return false;
            }
        }

        if($user->matchPassword($password) == true)
        {
            return true;
        }

        return false;
    }
?>

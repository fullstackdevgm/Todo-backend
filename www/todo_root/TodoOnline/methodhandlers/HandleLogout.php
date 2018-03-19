<?php

include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/php/SessionHandler.php');

if ($_COOKIE[TDO_ADMIN_IMPERSONATION_SESSION_COOKIE] && $_COOKIE[TDO_ADMIN_IMPERSONATION_REFERRER_URI_COOKIE]) {
    $referrer = base64_decode($_COOKIE[TDO_ADMIN_IMPERSONATION_REFERRER_URI_COOKIE]);
} else {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referrer = $_SERVER['HTTP_REFERER'];
    } else {
        $referrer = ".";
    }
}

TDOSession::logout();
//NCB - Taking out Facebook integration for initial release.
//$user = $facebook->getUser();
//if($user)
//{
//	$params = array( 'next' => $app_url);
//	$logoutURL = $facebook->getLogoutUrl($params); // $params is optional. 
//	header("Location:".$logoutURL);
//}
//else
	header("Location:".$referrer);

?>

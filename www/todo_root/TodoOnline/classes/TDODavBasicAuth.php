<?php
	
//include 'Sabre/HTTP/includes.php';
include_once 'Sabre/DAV/includes.php';
include_once('TodoOnline/base_sdk.php');	

/**
 *  TDODavBasicAuth
 *
 */

class TDODavBasicAuth extends Sabre_DAV_Auth_Backend_AbstractBasic
{
    /**
     * This variable holds the currently logged in username.
     *
     * @var string|null
     */
    //protected $currentUser;
	
    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @return bool
     */
	protected function validateUserPass($username, $password)
	{
        //error_log("Hey, validate user pass was called");
		$session = TDOSession::getInstance();
		return $session->caldavlogin($username, $password);
	}
	
    /**
     * Returns information about the currently logged in username.
     *
     * If nobody is currently logged in, this method should return null.
     *
     * @return string|null
     */
    //public function getCurrentUser()
	//{
    //    return $this->currentUser;
    //}
	
	
    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is succesful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool
     */
//    public function authenticate(Sabre_DAV_Server $server,$realm)
//	{
//        $auth = new Sabre_HTTP_BasicAuth();
//        $auth->setHTTPRequest($server->httpRequest);
//        $auth->setHTTPResponse($server->httpResponse);
//        $auth->setRealm($realm);
//        $userpass = $auth->getUserPass();
//        if (!$userpass)
//		{
//            $auth->requireLogin();
//            throw new Sabre_DAV_Exception_NotAuthenticated('No basic authentication headers were found');
//        }
//		
//        // Authenticates the user
//        if (!$this->validateUserPass($userpass[0],$userpass[1]))
//		{
//            $auth->requireLogin();
//            throw new Sabre_DAV_Exception_NotAuthenticated('Username or password does not match');
//        }
//        $this->currentUser = $userpass[0];
//        return true;
//    }

}

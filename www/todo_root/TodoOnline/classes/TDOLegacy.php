<?php
//      TDOLegacy
//      Class and methods to communicate with the Legacy Todo Online System


    // Defines to connect to the legacy server
    define('ACS_SERVICE_USER','user');
//    define('ACS_SERVICE_SESSION', 'session');
    define('ACS_SERVICE_SESSION', 'opensession');
    define('ACS_SERVICE_SYNC', 'todoapi');
    define('ACS_SERVICE_SKRECEIPT', 'receipt');
    
    define('ACS_TENANT_ID', 'Appigo');
    define('ACS_APP_ID', 'com.appigo.todo');
    define('ACS_API_TOKEN', '5d969fa1d3db24e4805278f6b2c442c5');

//    define('ACS_CONNECTION_ADDRESS', 'appigotodo.appspot.com');
    define('ACS_CONNECTION_ADDRESS', 'transition-dot-appigotodo.appspot.com');
    define('ACS_MIGRATION_DEVICE', 'TODOONLINE');

    
// include files
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');
require_once 'HTTP/Request2.php';    
	
class TDOLegacy
{
	private $_sessionId;
	private $_sharedSecret;
    private $_userName;
    private $_password;
    private $_userId;
    
	public function __construct()
	{
		$this->set_to_default();      
	}
    
	public function set_to_default()
	{
		// clears values without going to database
		// SimpleDB requires a value for every attribue...
		$this->_sessionId = NULL;
		$this->_sharedSecret = NULL;
		$this->_userName = NULL;
		$this->_password = NULL;
		$this->_userId = NULL;
	}
    
    
    public function buildRequestParameters($parameters, $sharedSecret = NULL)
    {
        $urlString = "";
        $hashString = "";
        
        if($sharedSecret != NULL)
            $hashString .= $sharedSecret;
        
        ksort($parameters);
        foreach ($parameters as $key => $val)
        {
            if(!empty($urlString))
            {
                $urlString .= "&";
            }
            
            $urlString .= $key;
            $urlString .= "=";
            $urlString .= urlencode($val);
            
            if(!empty($val))
            {
                $hashString .= $key;
                $hashString .= $val;
            }
        }

        $apiSig = md5($hashString);

        if(!empty($urlString))
        {
            $urlString .= "&";
        }
        
        $urlString .= "signature=";
        $urlString .= $apiSig;
        
        return $urlString;
    }
    
    
    private function makeRequest($request)
    {
        $response = NULL;
        
        try
        {
            $response = $request->send();
            if (200 == $response->getStatus())
            {
                $responseArray = json_decode($response->getBody(), true);
                if($responseArray == NULL)
                {
                    // check to see if Moki timed us out and the session is invalid
                    // they return this in XML no matter what
                    $bodyString = $response->getBody();
                    if( (strstr($bodyString, "<error><id>2</id>") != false) || (strstr($bodyString, "<error><id>1</id>") != false) )
                    {
                        $responseArray = array();
                        $errorArray = array();
                        $errorArray['id'] = 4801;
                        $errorArray['msg'] = "Todo Online invalid session";
                        $responseArray['error'] = $errorArray;
                        return $responseArray;
                    }
                    else
                    {
                        $responseArray = array();
                        $errorArray = array();
                        $errorArray['id'] = 4802;
                        $errorArray['msg'] = "Unable to parse response to json: " . $bodyString;
                        $responseArray['error'] = $errorArray;
                        return $responseArray;
                    }
                }
                return $responseArray;
            }
            else
            {
                $responseArray = array();
                $errorArray = array();
                $errorArray['id'] = $response->getStatus();
                $errorArray['msg'] = 'Unexpected HTTP status: ' . $response->getStatus() . ' ' . $response->getReasonPhrase();
                $responseArray['error'] = $errorArray;
                return $responseArray;
            }
        }
        catch (HTTP_Request2_Exception $e)
        {
            $responseArray = array();
            $errorArray = array();
            if(!empty($response))
                $errorArray['id'] = $response->getStatus();
            else
                $errorArray['id'] = 4803;
            $errorArray['msg'] = 'Exception:  ' . $e->getMessage();
            $responseArray['error'] = $errorArray;
            return $responseArray;
        }
        
        $responseArray = array();
        $errorArray = array();
        $errorArray['id'] = 4804;
        $errorArray['msg'] = 'No data was returned and there was no error, hmm!';
        $responseArray['error'] = $errorArray;
        return $responseArray;
    }
    

    /*
     array(1) {
     ["user"]=>
         array(10) {
         ["ispaidsub"]=>
         int(1)
         ["emailoptedout"]=>
         int(0)
         ["secondstosubexp"]=>
         int(15614149)
         ["email"]=>
         string(17) "calvin@appigo.com"
         ["emailverified"]=>
         int(1)
         ["gui_clickandaccept"]=>
         int(1)
         ["userid"]=>
         string(36) "44a707c0-6823-49c3-a6da-f9cf2412c439"
         ["lastname"]=>
         string(8) "Gaisford"
         ["firstname"]=>
         string(6) "Calvin"
         ["emailid"]=>
         string(12) "calvin+92205"
         }
     }
     */
    public function authUser($userEmail, $userPassword)
    {
        $parameters = array();
        $parameters['method'] = "authUser";
        $parameters['tenantid'] = ACS_TENANT_ID;
        $parameters['appid'] = ACS_APP_ID;
        $parameters['apitoken'] = ACS_API_TOKEN;
        $parameters['password'] = $userPassword;
        $parameters['email'] = $userEmail;
        $parameters['resformat'] = "json";
        
        $encodedParameters = $this->buildRequestParameters($parameters);
        
        $requestString = "https://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_USER."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        
        //var_dump($responseArray);
        return $responseArray;
    }
    
    
    /*
     array(1) {
     ["session"]=>
         array(9) {
         ["lasttaskedit"]=>
         string(20) "2012-07-18T23:06:31Z"
         ["ispaidsub"]=>
         int(1)
         ["syncversion"]=>
         string(3) "1.0"
         ["secondstosubexp"]=>
         int(15614711)
         ["syncrevision"]=>
         string(4) "1175"
         ["usercreatedate"]=>
         string(20) "2010-09-24T17:28:07Z"
         ["sessionid"]=>
         string(32) "d008108c25a15fce75f75af95b84d51d"
         ["lastlistedit"]=>
         string(20) "2012-06-06T19:48:58Z"
         ["sharedSecret"]=>
         string(32) "df2b05a0581439678a70c8a22e182266"
         }
     }
     */
    public function startSyncSession($userId, $deviceId)
    {
        $parameters = array();
        $parameters['method'] = "startSyncSession";
        $parameters['tenantid'] = ACS_TENANT_ID;
        $parameters['appid'] = ACS_APP_ID;
        $parameters['apitoken'] = ACS_API_TOKEN;
        $parameters['userid'] = $userId;
        $parameters['deviceid'] = $deviceId;
        $parameters['resformat'] = "json";
        
        $encodedParameters = $this->buildRequestParameters($parameters);
        
        $requestString = "https://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SESSION."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    
    
    /*
     array(1) {
     ["session"]=>
         array(5) {
         ["lasttaskedit"]=>
         string(20) "2012-07-18T23:06:31Z"
         ["syncversion"]=>
         string(3) "1.0"
         ["syncrevision"]=>
         string(4) "1175"
         ["sessionid"]=>
         string(32) "d008108c25a15fce75f75af95b84d51d"
         ["lastlistedit"]=>
         string(20) "2012-06-06T19:48:58Z"
         }
     }
     */
    public function endSyncSession($sessionId)
    {
        $parameters = array();
        $parameters['method'] = "endSyncSession";
        $parameters['tenantid'] = ACS_TENANT_ID;
        $parameters['appid'] = ACS_APP_ID;
        $parameters['apitoken'] = ACS_API_TOKEN;
        $parameters['sessionid'] = $sessionId;
        $parameters['resformat'] = "json";
        
        $encodedParameters = $this->buildRequestParameters($parameters);
        
        $requestString = "https://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SESSION."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    

    /*
     array(1) {
     ["lists"]=>
         array(1) {
             ["list"]=>
             array(4) {
                 [0]=>
                 array(3) {
                     ["title"]=>
                     string(7) "Camping"
                     ["color"]=>
                     string(59) "0.271109425696:0.812413074076:0.500734676374:1.000000000000"
                     ["listid"]=>
                     string(36) "0426e100-b0eb-42b9-9d24-187e25853800"
                 }
                 [1]=>
                 array(3) {
                     ["title"]=>
                     string(4) "Work"
                     ["color"]=>
                     string(59) "0.071000002325:0.078000001609:0.980000019073:1.000000000000"
                     ["listid"]=>
                     string(36) "6d3fc0ab-8fd0-43e3-98e0-32f128d972fa"
                 }
                 [2]=>
                 array(3) {
                     ["title"]=>
                     string(6) "Appigo"
                     ["color"]=>
                     string(59) "0.614458121359:0.709450242110:0.822527421871:1.000000000000"
                     ["listid"]=>
                     string(36) "9887ba89-aeca-4799-b181-254fc918f3ce"
                 }
                 [3]=>
                 array(3) {
                     ["title"]=>
                     string(4) "Home"
                     ["color"]=>
                     string(59) "0.250999987125:0.486000001431:0.059000000358:1.000000000000"
                     ["listid"]=>
                     string(36) "fcb92dd6-782d-411b-bd17-696fc99cec7b"
                 }
             }
         }
     }
     */
    public function getLists($sessionId, $sharedSecret)
    {
        $parameters = array();
        $parameters['method'] = "getLists";
        $parameters['sessionid'] = $sessionId;
        $parameters['resformat'] = "json";
        
        $encodedParameters = $this->buildRequestParameters($parameters, $sharedSecret);
        
        $requestString = "http://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SYNC."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    

    /*
     array(1) {
     ["contexts"]=>
         array(1) {
         ["context"]=>
             array(4) {
                 [0]=>
                 array(2) {
                     ["title"]=>
                     string(6) "Office"
                     ["contextid"]=>
                     string(36) "157e1d49-f51e-44ac-ae13-0adb5a0c4133"
                 }
                 [1]=>
                 array(2) {
                     ["title"]=>
                     string(6) "School"
                     ["contextid"]=>
                     string(36) "1b33278c-2e06-4dc0-9d37-dc151d10422e"
                 }
                 [2]=>
                 array(2) {
                     ["title"]=>
                     string(7) "Library"
                     ["contextid"]=>
                     string(36) "97a0c530-1708-42b6-82bf-079701682d65"
                 }
                 [3]=>
                 array(2) {
                     ["title"]=>
                     string(8) "Downtown"
                     ["contextid"]=>
                     string(36) "a0fa9199-9c56-4606-95a7-516681fa767b"
                 }
             }
         }
     }
     */
    public function getContexts($sessionId, $sharedSecret)
    {
        $parameters = array();
        $parameters['method'] = "getContexts";
        $parameters['sessionid'] = $sessionId;
        $parameters['resformat'] = "json";
        
        $encodedParameters = $this->buildRequestParameters($parameters, $sharedSecret);
        
        $requestString = "http://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SYNC."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    
    
    /*
     array(1) {
     ["tasks"]=>
     array(3) {
     ["lasttaskedit"]=>
     string(20) "2012-07-20T04:05:57Z"
     ["numreturned"]=>
     int(15)
     ["task"]=>
     array(15) {
     [0]=>
     array(7) {
     ["completeddate"]=>
     string(20) "2012-07-20T04:05:57Z"
     ["title"]=>
     string(13) "will it sync?"
     ["taskid"]=>
     string(36) "53ff43d9-9407-4b41-82b6-7d3492f48b8e"
     ["duedate"]=>
     string(20) "2012-06-05T00:00:00Z"
     ["lastupdated"]=>
     string(20) "2012-07-20T04:05:57Z"
     ["sort_order"]=>
     int(0)
     ["completed"]=>
     int(1)
     }
     [4]=>
     array(13) {
     ["location_alert"]=>
     string(0) ""
     ["taskid"]=>
     string(36) "c1236da9-4bc2-436f-9183-fc0fb69870ff"
     ["lastupdated"]=>
     string(20) "2012-06-06T19:49:01Z"
     ["repeat"]=>
     int(0)
     ["type"]=>
     int(0)
     ["title"]=>
     string(13) "testing again"
     ["starred"]=>
     int(0)
     ["priority"]=>
     int(0)
     ["type_data"]=>
     string(0) ""
     ["duedate"]=>
     string(20) "2012-06-05T00:00:00Z"
     ["sort_order"]=>
     int(0)
     ["notes"]=>
     string(0) ""
     ["completed"]=>
     int(0)
     }
     [6]=>
     array(13) {
     ["location_alert"]=>
     string(0) ""
     ["taskid"]=>
     string(36) "a430be2f-2f5b-417a-bb7e-3a4e701cbaf4"
     ["lastupdated"]=>
     string(20) "2012-06-06T19:48:59Z"
     ["repeat"]=>
     int(0)
     ["type"]=>
     int(0)
     ["title"]=>
     string(31) "New Task with no dropbox client"
     ["starred"]=>
     int(0)
     ["priority"]=>
     int(0)
     ["type_data"]=>
     string(0) ""
     ["duedate"]=>
     string(20) "2012-06-05T00:00:00Z"
     ["sort_order"]=>
     int(0)
     ["notes"]=>
     string(0) ""
     ["completed"]=>
     int(0)
     }
     [7]=>
     array(13) {
     ["location_alert"]=>
     string(0) ""
     ["taskid"]=>
     string(36) "784e96f0-54a6-42c0-9a8d-30e37b8b81e3"
     ["lastupdated"]=>
     string(20) "2012-06-06T19:48:59Z"
     ["repeat"]=>
     int(0)
     ["type"]=>
     int(0)
     ["title"]=>
     string(20) "New Task Again Today"
     ["starred"]=>
     int(0)
     ["priority"]=>
     int(0)
     ["type_data"]=>
     string(0) ""
     ["duedate"]=>
     string(20) "2012-06-05T00:00:00Z"
     ["sort_order"]=>
     int(0)
     ["notes"]=>
     string(0) ""
     ["completed"]=>
     int(0)
     }
     }
     }
     }
     */
    public function getTasks($sessionId, $sharedSecret, $offset = NULL)
    {
        $parameters = array();
        $parameters['method'] = "getTasks";
        $parameters['sessionid'] = $sessionId;
        $parameters['resformat'] = "json";
        $parameters['bucketsize'] = "500";
        
        if($offset != NULL)
            $parameters['offset'] = $offset;
            

//        if($completed == false)
//            $parameters['completed'] = '0';
//        else
//            $parameters['completed'] = '1';

        $encodedParameters = $this->buildRequestParameters($parameters, $sharedSecret);
        
        $requestString = "http://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SYNC."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    
    
    /*
     array(1) {
     ["notifications"]=>
     array(3) {
     ["lastnotificationedit"]=>
     string(20) "2012-07-20T04:36:02Z"
     ["numreturned"]=>
     int(2)
     ["notification"]=>
     array(2) {
     [0]=>
     array(5) {
     ["uid"]=>
     string(36) "4e739948-d970-4b29-b343-f1f9d3e2c3d8"
     ["soundname"]=>
     string(8) "None.caf"
     ["taskid"]=>
     string(36) "cf413fe7-31b9-4c64-82f1-5e1e6a4ff7b4"
     ["triggerdate"]=>
     string(20) "2012-05-15T22:45:00Z"
     ["triggeroffset"]=>
     int(900)
     }
     [1]=>
     array(5) {
     ["uid"]=>
     string(36) "0bdc202e-99a3-42bb-a0e1-ed9832787f9d"
     ["soundname"]=>
     string(9) "Bells.caf"
     ["taskid"]=>
     string(36) "cf413fe7-31b9-4c64-82f1-5e1e6a4ff7b4"
     ["triggerdate"]=>
     string(20) "2012-05-15T22:55:00Z"
     ["triggeroffset"]=>
     int(300)
     }
     }
     }
     }
     */
    public function getNotifications($sessionId, $sharedSecret, $offset = NULL)
    {
        $parameters = array();
        $parameters['method'] = "getNotifications";
        $parameters['sessionid'] = $sessionId;
        $parameters['resformat'] = "json";
        $parameters['bucketsize'] = "500";
        
        if($offset != NULL)
            $parameters['offset'] = $offset;
        
        
        //        if($completed == false)
        //            $parameters['completed'] = '0';
        //        else
        //            $parameters['completed'] = '1';
        
        $encodedParameters = $this->buildRequestParameters($parameters, $sharedSecret);
        
        $requestString = "http://".ACS_CONNECTION_ADDRESS."/".ACS_SERVICE_SYNC."?".$encodedParameters;
        $request = new HTTP_Request2($requestString, HTTP_Request2::METHOD_GET);
        $request->setConfig("ssl_capath", CACERTS_PATH);
        $responseArray = $this->makeRequest($request);
        return $responseArray;
    }
    
    
    public function userIsBeingMigrated($loginName)
    {
        $link = TDOUtil::getDBLink();
        if(!$link) 
            return false;
    
		$escapedUsername = mysql_real_escape_string($loginName, $link);
        
        $sqlResult = mysql_query("SELECT userid FROM tdo_user_migrations WHERE (username='$escapedUsername') AND (migration_completion_date = 0)");
		if(!$sqlResult)
		{
            error_log("TDOLegacy::userIsBeingMigrated failed with error: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        else
        {
            if($row = mysql_fetch_array($sqlResult))
            {
                if(isset($row['userid']))
                    $userid = $row['userid'];
            }
            else
            {
                //error_log("TDOLegacy::userIsBeingMigrated found no user matching");
                return false;
            }
        }

        return true;
    }
    
    
    public static function startMigrationForLegacyUser($loginName, $password)
    {
        $result = array();

        $tdoLegacy = new TDOLegacy();
        
        // ----------------------------------
        // Check to see if the user is already in the migration queue or is done being migrated
        // ----------------------------------
        if($tdoLegacy->userIsBeingMigrated($loginName) == true)
        {
            $error = array();
            $error['id'] = 4805;
            $error['msg'] = "User is already being migrated";
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        // ----------------------------------
        // Read the user from legacy system
        // ----------------------------------
        
        $response = $tdoLegacy->authUser($loginName, $password);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            $result['error'] = $error;
            error_log("TDOLegacy::startMigrationForLegacyUser failed makeing the authUser call with error: " . $error['msg']);
            return $result;
        }
        
        if(empty($response['user']))
        {
            $error = array();
            $error['id'] = 4806;
            $error['msg'] = "Expected a result with a user object in it and didn't get it";
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $user = $response['user'];
        
        if(empty($user['userid']))
        {
            $error = array();
            $error['id'] = 4807;
            $error['msg'] = "Legacy user object was missing a userid";
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        // ----------------------------------
        // Create the user in new system
        // ----------------------------------
        $userId = $user['userid']; // string(36)
//        $emailOptedOut = $tdoLegacy['emailoptedout']; // int(0)
//        $emailverified = $tdoLegacy['emailverified']; // int(1)
//        $gui_clickandaccept = $tdoLegacy['gui_clickandaccept']; // int(1)
//        $lastname = $tdoLegacy['lastname']; // string(8) "Gaisford"
//        $firstname = $tdoLegacy['firstname']; // string(6) "Calvin"
//        $emailid = $tdoLegacy['emailid']; // string(12) "calvin+92205")
        
        //var_dump($user);
        $tdoUser = new TDOUser();
        
        $tdoUser->setUserId($userId);
        
        if(strlen($loginName) > USER_NAME_LENGTH)
        {
            $error = array();
            $error['id'] = 4808;
            $error['msg'] = "Failed creating user because username is too long ".$userId;
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        if(TDOUser::existsUsername($loginName))
        {
            $error = array();
            $error['id'] = 4865;
            $error['msg'] = "Failed creating user because username exists ".$loginName;
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $tdoUser->setUsername($loginName);
        
        if(strlen($password) > PASSWORD_LENGTH)
        {
            $error = array();
            $error['id'] = 4809;
            $error['msg'] = "Failed creating user because password is too long ".$userId;
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }     
        $tdoUser->setPassword($password);
        
        if(!empty($user['firstname']))
            $tdoUser->setFirstName($user['firstname']);
        if(!empty($user['lastname']))
            $tdoUser->setLastName($user['lastname']);
        if(isset($user['emailverified']))
            $tdoUser->setEmailVerified($user['emailverified']);
        
        if($tdoUser->addUser(false, true) == false)
        {
            $error = array();
            $error['id'] = 4910;
            $error['msg'] = "Failed creating user with legacy user values for userid: ".$userId;
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $result['userid'] = $userId;
        
        // calculate the amount of time to add to subscription

        // figure out when it will expire, 0 is the default
        $secondsToExpiration = 0;
        $addedToSubscription = 0;
        $originalSubscriptionExpiration = 0;
        
        if(!empty($user['secondstosubexp']))
        {
            $expValue = $user['secondstosubexp'];
            if($expValue > 0)
            {
                $secondsToExpiration = $expValue;
                $originalSubscriptionExpiration = time() + $expValue;
            }
        }
        
        // if they are a paid user and are not expired, add a bonus to their subscription
        if(!empty($user['ispaidsub']))
        {
            if($secondsToExpiration > 0)
            {
                $addedToSubscription = SUBSCRIPTION_MIGRATION_BONUS;
                $secondsToExpiration += $addedToSubscription;
            }
        }
        
        // if their expiration is less than the default trial, give them the default trial
        if($secondsToExpiration < SUBSCRIPTION_DEFAULT_TRIAL_DAYS)
        {
            $addedToSubscription = SUBSCRIPTION_MIGRATION_BONUS;
            $secondsToExpiration = SUBSCRIPTION_MIGRATION_BONUS;
        }
        
        $newExpirationDate = time() + (int)$secondsToExpiration;
        
        // Add record into the tdo_user_migrations to migrate the user
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4811;
            $error['msg'] = "Failed setting up user migration for userid: ".$userId;
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }

		$escapedUsername = mysql_real_escape_string($loginName, $link);
		$escapedPassword = mysql_real_escape_string($password, $link);
		$escapedUserId = mysql_real_escape_string($userId, $link);
        
		$sql = "INSERT INTO tdo_user_migrations (userid, username, password, original_subscription_expiration_date, subscription_time_added, subscription_expiration_date) VALUES ('$escapedUserId', '$escapedUsername', '$escapedPassword', $originalSubscriptionExpiration, $addedToSubscription, $newExpirationDate)";
		$response = mysql_query($sql, $link);
		if(!$response)
		{
            $error = array();
            $error['id'] = 4812;
            $error['msg'] = "Error adding user to migration queue: ".$userId." with error: ".mysql_error();
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            
            TDOUtil::closeDBLink($link);
            return $result;
        }

        TDOUtil::closeDBLink($link);
        
        $result['subscription_time_added'] = $addedToSubscription;
        
        return $result;
    }


	public static function markUserRecordForMigration($daemonID = NULL)
	{
        $result = array();
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4813;
            $error['msg'] = "Error linking to Database";
            error_log("TDOLegacy::markUserRecordForMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        // create a temporary marker to migrating users
        $daemonMarkerId = "EC3A89BD-C176-4656-B669-C80036C72D4D";
        if(!empty($daemonID))
            $daemonMarkerId = mysql_real_escape_string($daemonID);
        
        
        $sqlResult = mysql_query("SELECT COUNT(*) FROM tdo_user_migrations where daemonid='$daemonMarkerId' AND migration_completion_date = 0");
        if($sqlResult)
        {
            $total = mysql_fetch_array($sqlResult);
            if($total && isset($total[0]))
            {
                if(intval($total[0]) > 0)
                {
                    TDOUtil::closeDBLink($link);
                    
                    $result['records_marked_count'] = $total[0];
                    return $result;
                }
            }
        }
        
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET daemonid='$daemonMarkerId' WHERE (daemonid='' OR daemonid IS NULL) AND (migration_completion_date = 0) LIMIT 1");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4814;
            $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
            error_log("TDOLegacy::markUserRecordForMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
		
		$markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        
        $result['records_marked_count'] = $markedRowCount;
		return $result;
	}
    
    
	public static function markUserRecordForFailedMigration($daemonID = NULL)
	{
        $result = array();
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4815;
            $error['msg'] = "Error linking to Database";
            error_log("TDOLegacy::markUserRecordForMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        // create a temporary marker to migrating users
        $daemonMarkerId = "EC3A89BD-C176-4656-B669-C80036C72D4D";
        if(!empty($daemonID))
            $daemonMarkerId = mysql_real_escape_string($daemonID);
        
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET daemonid='FAILED' WHERE (daemonid='$daemonMarkerId') AND (migration_completion_date = 0) LIMIT 1");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4816;
            $error['msg'] = "Unable to mark failed migration users for migration: " . mysql_error();
            error_log("TDOLegacy::markUserRecordForFailedMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
		
		$markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        
        $result['records_marked_count'] = $markedRowCount;
		return $result;
	}    
    
    
    
    public static function userCanReMigrate($userId)
    {
        $link = TDOUtil::getDBLink();
        if(!$link) 
            return false;
        
		$escapedUserId = mysql_real_escape_string($userId, $link);
        
        $sqlResult = mysql_query("SELECT userid FROM tdo_user_migrations WHERE (userid='$escapedUserId') AND (daemonid='order47')");
		if(!$sqlResult)
		{
            error_log("TDOLegacy::userCanReMigrate failed with error: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
        else
        {
            if($row = mysql_fetch_array($sqlResult))
            {
                if(isset($row['userid']))
                {
                    TDOUtil::closeDBLink($link);
                    return true;
                }
            }
        }
        
        TDOUtil::closeDBLink($link);
        return false;
    }
    
    
    
	public static function enableUserRecordForReMigration($userID)
	{
        $result = array();
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4817;
            $error['msg'] = "Error linking to Database";
            error_log("TDOLegacy::enableUserRecordForReMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
		$escapedUserId = mysql_real_escape_string($userID, $link);
        
        
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET daemonid='order47' WHERE userid='$escapedUserId'");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4818;
            $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
            error_log("TDOLegacy::enableUserRecordForReMigration failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
		
		$markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        
        $result['records_marked_count'] = $markedRowCount;
		return $result;
	}    

    
	public static function reMigrateUser($userID, $password)
	{
        $result = array();
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4819;
            $error['msg'] = "Error linking to Database";
            error_log("TDOLegacy::reMigrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $username = TDOUser::usernameForUserId($userID);
        
        $tdoLegacy = new TDOLegacy();

        // check to see that the user can login
        $response = $tdoLegacy->authUser($username, $password);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            $result['error'] = $error;
            error_log("TDOLegacy::startMigrationForLegacyUser failed makeing the authUser call with error: " . $error['msg']);
            return $result;
        }
        
        if(empty($response['user']))
        {
            $error = array();
            $error['id'] = 4820;
            $error['msg'] = "Expected a result with a user object in it and didn't get it";
            error_log("TDOLegacy::startMigrationForLegacyUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
		$escapedUsername = mysql_real_escape_string($username, $link);
		$escapedPassword = mysql_real_escape_string($password, $link);
        
        
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET username='$escapedUsername', password='$escapedPassword', daemonid=NULL, migration_completion_date=0  WHERE userid='$userID'");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4821;
            $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
            error_log("TDOLegacy::reMigrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
		
		$markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        
        $result['records_marked_count'] = $markedRowCount;
		return $result;
	}    
    
    

    
    // Migration method used to migrate a user from the legacy system to the new system
    public static function processMarkedRecords($daemonID = NULL)
    {
        $result = array();
        
        $link = TDOUtil::getDBLink();
        if(!$link) 
        {
            $error = array();
            $error['id'] = 4822;
            $error['msg'] = "Error linking to Database";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        // create a temporary marker to migrating users
        $daemonMarkerId = "EC3A89BD-C176-4656-B669-C80036C72D4D";
        if(!empty($daemonID))
            $daemonMarkerId = mysql_real_escape_string($daemonID);
        
		$sqlResult = mysql_query("SELECT userid, username, password FROM tdo_user_migrations WHERE daemonid='$daemonMarkerId' AND migration_completion_date = 0 LIMIT 1");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4823;
            $error['msg'] = "Error retrieving users to be migrated from DB: " . mysql_error();
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
        else
        {
            if($row = mysql_fetch_array($sqlResult))
            {
                $userid = $row['userid'];
                $loginName = $row['username'];
                $password = $row['password'];
            }
            else
            {
                return $result;
            }
        }
        
        $migrationDate = time();
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET migration_last_attempt=$migrationDate  WHERE userid='$userid'");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4824;
            $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }

        // close the DB for now, we'll come back later
        TDOUtil::closeDBLink($link);
        $link = NULL;
        
        $tdoLegacy = new TDOLegacy();
        
        // ----------------------------------
        // Read the user from legacy system
        // ----------------------------------
        $response = $tdoLegacy->authUser($loginName, $password);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            $result['error'] = $error;
            error_log("TDOLegacy::migrateUser failed makeing the authUser call with error: " . $error['msg']);
            return $result;
        }
        
        if(empty($response['user']))
        {
            $error = array();
            $error['id'] = 4825;
            $error['msg'] = "Expected a result with a user object in it and didn't get it";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $user = $response['user'];
        
        if(empty($user['userid']))
        {
            $error = array();
            $error['id'] = 4826;
            $error['msg'] = "Expected a result with a userid in it and didn't get it";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $userId = $user['userid']; // string(36)
        $emailOptedOut = $user['emailoptedout']; // int(0)
        $emailverified = $user['emailverified']; // int(1)
        $gui_clickandaccept = $user['gui_clickandaccept']; // int(1)
        $lastname = $user['lastname']; // string(8) "Gaisford"
        $firstname = $user['firstname']; // string(6) "Calvin"
        // $emailid = $user['emailid']; // string(12) "calvin+92205")

        // ----------------------------------
        // User should have been created already, process the user
        // ----------------------------------
        
        $result['userid'] = $userId;
        
        // ----------------------------------
        // Add the user's subscription
        // ----------------------------------
        
        // figure out when it will expire, 0 is the default
        $secondsToExpiration = 0;
        if(!empty($user['secondstosubexp']))
        {
            $tmpSeconds = $user['secondstosubexp'];
            if($tmpSeconds > 0)
                $secondsToExpiration = $tmpSeconds;
        }
        
        // if they are a paid user and are not expired, add a bonus to their subscription
        if(!empty($user['ispaidsub']))
        {
            if($secondsToExpiration > 0)
                $secondsToExpiration += SUBSCRIPTION_MIGRATION_BONUS;
        }

        // if their expiration is less than the default trial, give them the default trial
        if($secondsToExpiration < SUBSCRIPTION_DEFAULT_TRIAL_DAYS)
            $secondsToExpiration = SUBSCRIPTION_DEFAULT_TRIAL_DAYS;
        

        $expirationDate = time() + (int)$secondsToExpiration;
        
        
        $currentSubscription = TDOSubscription::getSubscriptionForUserID($userId);
        
        if(!empty($currentSubscription))
        {
            // if the expiration date of the current subscription is newer, don't update it
            if( intval($currentSubscription->getExpirationDate()) < intval($expirationDate) )
            {    
                $subscriptionID = $currentSubscription->getSubscriptionID();
                
                if(TDOSubscription::updateSubscriptionWithNewExpirationDate($subscriptionID, $expirationDate, SUBSCRIPTION_TYPE_YEAR, SUBSCRIPTION_LEVEL_MIGRATED) == false)
                {
                    $error = array();
                    $error['id'] = 4827;
                    $error['msg'] = "Unable to update subscription for the user: ".$userId;
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    return $result;
                }
            }
        }
        else
        {
            $subscriptionID = TDOSubscription::createSubscription($userId, $expirationDate, SUBSCRIPTION_TYPE_YEAR, SUBSCRIPTION_LEVEL_MIGRATED);
            if($subscriptionID == false)
            {
                $error = array();
                $error['id'] = 4828;
                $error['msg'] = "Unable to create a subscription for the user: ".$userId;
                error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                $result['error'] = $error;
                return $result;
            }
        }
        
        $result['subscription_migrated'] = true;
        
        // ----------------------------------
        // Begin Sync to move over data
        // ----------------------------------
        
        $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            if($error['id'] == 30)
            {
                // user's account is expired, we need to move on

                $link = TDOUtil::getDBLink();
                if(!$link)
                {
                    $error = array();
                    $error['id'] = 4829;
                    $error['msg'] = "Error linking to Database";
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    return $result;
                }
                
                $migrationDate = time();
                $sqlResult = mysql_query("UPDATE tdo_user_migrations SET migration_completion_date=$migrationDate, password='legacy_account_expired' WHERE userid='$userid'");
                if(!$sqlResult)
                {
                    $error = array();
                    $error['id'] = 4830;
                    $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
                TDOUtil::closeDBLink($link);
                return $result;
            }
            else
            {
                $result['error'] = $error;
                error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                return $result;
            }
        }
        
        if(empty($response['session']))
        {
            $error = array();
            $error['id'] = 4831;
            $error['msg'] = "Expected a result with a session in it and didn't get it";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $session = $response['session'];
        
        if(empty($session['sessionid']))
        {
            $error = array();
            $error['id'] = 4832;
            $error['msg'] = "Expected a result with a sessionid in it and didn't get it";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            return $result;
        }
        
        $sessionId = $session['sessionid'];
        
        if(empty($session['sharedSecret']))
        {
            $error = array();
            $error['id'] = 4833;
            $error['msg'] = "Expected a result with a sharedSecret in it and didn't get it";
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;

            $tdoLegacy->endSyncSession($sessionId);
            return $result;
        }
        
        
//        error_log("Sleeping for 365 seconds...");
//        sleep (365);
//        error_log("Getting Lists...");
        
        
        $sharedSecret = $session['sharedSecret'];
        
        error_log("Migrating Lists...");
        
        $response = $tdoLegacy->getLists($sessionId, $sharedSecret);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            if( ($error['id'] == 1) || ($error['id'] == 2) )
            {
                error_log("Todo Online Session timed out, renewing session to continue....");

                $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);
                
                if(!empty($response['error']))
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                    return $result;
                }
                
                if(empty($response['session']) || empty($session['sessionid']) || empty($session['sharedSecret']))
                {
                    $error = array();
                    $error['id'] = 4834;
                    $error['msg'] = "Session timed out and unable to get new session from legacy Todo Online";
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    return $result;
                }
                
                $session = $response['session'];
                $sessionId = $session['sessionid'];
                $sharedSecret = $session['sharedSecret'];
                
                $response = $tdoLegacy->getLists($sessionId, $sharedSecret);
                if(!empty($response['error']))
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                    return $result;
                }
            }
            else
            {
                $result['error'] = $error;
                error_log("TDOLegacy::migrateUser failed makeing the getLists call with error: " . $error['msg']);
                $tdoLegacy->endSyncSession($sessionId);
                return $result;
            }
        }
        
        $link = TDOUtil::getDBLink();
        if(empty($link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4835;
            error_log("TDOLegacy::migrateUser failed with error: ".$error['msg']);
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);
            
            return $result;
        }
        
//        if(!mysql_query("set names 'utf8'", $link))
//        {
//            error_log("Failed to set UTF8 as default char set in mysql");
//        }
        
        
        
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4836;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);
            
            TDOUtil::closeDBLink($link);
            
            return $result;
        }
        
        $listsMigrated = array();
        if(!empty($response['lists']))
        {
            $lists = $response['lists'];
            if(!empty($lists['list']))
            {
                $listArray = $lists['list'];

                // Moki really bunged stuff up here so check if there is a title key
                // and if there is, put the values inside an array so processing is the
                // same for one item or multiple items
                if(array_key_exists('title', $listArray) == true)
                {
                    $listArray = array();
                    $listArray[] = $lists['list'];
                }
                
                foreach($listArray as $list)
                {
                    $title = $list['title'];
                    $color = $list['color'];
                    $listid = $list['listid'];
                    
                    $performAdd = true;
                    $tdoList = TDOList::getListForListid($listid, $link);
                    if($tdoList == false)
                        $tdoList = new TDOList();
                    else
                    {
                        //error_log("List already exists, updating list...");
                        $performAdd = false;
                    }
                    
                    $tdoList->setName($title);
                    $tdoList->setCreator($userId);
                    $tdoList->setListId($listid);
                    
                    if($performAdd == true)
                        $opRC = $tdoList->addList($userId, NULL, $link);
                    else
                        $opRC = $tdoList->updateList($userId, $link);
                    
                    if($opRC == false)
                    {
                        $error = array();
                        $error['id'] = 4837;
                        $error['msg'] = "Failed to create list";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        
                        $tdoLegacy->endSyncSession($sessionId);
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        
                        return $result;
                    }
                    else
                    {
                        if(!empty($color))
                        {
                            $rgbValues = explode(":", $color);
                            
                            $red = (int) (floatval($rgbValues[0]) * 255);
                            $green = (int) (floatval($rgbValues[1]) * 255);
                            $blue = (int) (floatval($rgbValues[2]) * 255);
                                
                            $newColorString = strval($red).",".strval($green).",".strval($blue);

                            
                            $listSettings = TDOListSettings::getListSettingsForUser($tdoList->listId(), $userId, $link);
                            if($listSettings)
                            {
                                $listSettings->setColor($newColorString);
                                
                                if(!$listSettings->updateListSettings($tdoList->listId(), $userId, $link))
                                {
                                    error_log("Unable to update list settings when trying to add the color");
                                }
                            }
                        }

                        $listsMigrated[] = $list;
                        TDOChangeLog::addChangeLog($tdoList->listId(), $userId, $tdoList->listId(), $tdoList->name(), ITEM_TYPE_LIST, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);
                    }
                }
            }
        }
        
        if(!mysql_query("COMMIT", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4838;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);

            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return $result;
        }
        
        
        // add migrated lists to the results
        $result['lists_migrated'] = $listsMigrated;

        error_log("Migrating Contexts...");
        
        $response = $tdoLegacy->getContexts($sessionId, $sharedSecret);
        
        if(!empty($response['error']))
        {
            $error = $response['error'];
            if( ($error['id'] == 1) || ($error['id'] == 2) )
            {
                error_log("Todo Online Session timed out, renewing session to continue....");

                $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);
                
                if(!empty($response['error']))
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
                
                if(empty($response['session']) || empty($session['sessionid']) || empty($session['sharedSecret']))
                {
                    $error = array();
                    $error['id'] = 4839;
                    $error['msg'] = "Session timed out and unable to get new session from legacy Todo Online";
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
                
                $session = $response['session'];
                $sessionId = $session['sessionid'];
                $sharedSecret = $session['sharedSecret'];
                
                $response = $tdoLegacy->getContexts($sessionId, $sharedSecret);
                if(!empty($response['error']))
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
            }
            else
            {
                $result['error'] = $error;
                error_log("TDOLegacy::migrateUser failed makeing the getContexts call with error: " . $error['msg']);
                $tdoLegacy->endSyncSession($sessionId);
                TDOUtil::closeDBLink($link);
                return $result;
            }
        }
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4840;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);
            
            TDOUtil::closeDBLink($link);
            
            return $result;
        }
        
        $contextsMigrated = array();

        if(!empty($response['contexts']))
        {
            $contexts = $response['contexts'];
            if(!empty($contexts['context']))
            {
                $contextArray = $contexts['context'];

                // Moki really bunged stuff up here so check if there is a title key
                // and if there is, put the values inside an array so processing is the
                // same for one item or multiple items
                if(array_key_exists('title', $contextArray) == true)
                {
                    $contextArray = array();
                    $contextArray[] = $contexts['context'];
                }
                
                foreach($contextArray as $context)
                {
                    $title = $context['title'];
                    $contextid = $context['contextid'];

                    
                    $performAdd = true;
                    $tdoContext = TDOContext::getContextForContextid($contextid, $link);
                    if($tdoContext == false)
                        $tdoContext = new TDOContext();
                    else
                    {
                        //error_log("Context already exists, updating context...");
                        $performAdd = false;
                    }
                    
                    $tdoContext->setName($title);
                    $tdoContext->setUserid($userId);
                    $tdoContext->setContextid($contextid);
                    
                    
                    if($performAdd == true)
                        $opRC = $tdoContext->addContext($link);
                    else
                        $opRC = $tdoContext->updateContext($link);
                    
                    if($opRC == false)
                    {
                        $error = array();
                        $error['id'] = 4841;
                        $error['msg'] = "Failed to create context";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        
                        $tdoLegacy->endSyncSession($sessionId);
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    else
                    {
                        $contextsMigrated[] = $context;
                        TDOChangeLog::addChangeLog($tdoContext->getContextid(), $userId, $tdoContext->getContextid(), $tdoContext->getName(), ITEM_TYPE_CONTEXT, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);
                    }
                }
            }
        }
        
        if(!mysql_query("COMMIT", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4842;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);

            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return $result;
        }
        
        
        // add migrated contexts to the results
        $result['contexts_migrated'] = $contextsMigrated;
        
        error_log("Migrating Tasks...");
        
        
        $tasksMigrated = array();
        $childTasks = array();
        
        $hasMore = true;
        $serverOffset = NULL;
        
        while($hasMore)
        {
            //error_log("Looping to get more tasks...");

            $response = $tdoLegacy->getTasks($sessionId, $sharedSecret, $serverOffset);
            
            if(!empty($response['error']))
            {
                $error = $response['error'];
                if( ($error['id'] == 1) || ($error['id'] == 2) )
                {
                    error_log("Todo Online Session timed out, renewing session to continue....");
                    
                    $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);
                    
                    if(!empty($response['error']))
                    {
                        $result['error'] = $error;
                        error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    if(empty($response['session']) || empty($session['sessionid']) || empty($session['sharedSecret']))
                    {
                        $error = array();
                        $error['id'] = 4843;
                        $error['msg'] = "Session timed out and unable to get new session from legacy Todo Online";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    $session = $response['session'];
                    $sessionId = $session['sessionid'];
                    $sharedSecret = $session['sharedSecret'];
                    
                    $response = $tdoLegacy->getTasks($sessionId, $sharedSecret, $serverOffset);
                    if(!empty($response['error']))
                    {
                        $result['error'] = $error;
                        error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                }
                else
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the getTasks call with error: " . $error['msg']);
                    $tdoLegacy->endSyncSession($sessionId);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
            }

            
            if(!empty($response['tasks']))
            {
                $tasks = $response['tasks'];
                
                if(empty($tasks['task']))
                {
                    $hasMore = false;
                    continue;
                }
                
                if(isset($tasks['offset']))
                    $serverOffset = $tasks['offset'];
                
                $numReturned = $tasks['numreturned'];
                
                if($numReturned < 500)
                    $hasMore = false;
                
                
                $taskArray = $tasks['task'];
                // Moki really bunged stuff up here so check if there is a title key
                // and if there is, put the values inside an array so processing is the
                // same for one item or multiple items
                if(array_key_exists('title', $taskArray) == true)
                {
                    $taskArray = array();
                    $taskArray[] = $tasks['task'];
                }
                
                if(!mysql_query("START TRANSACTION", $link))
                {
                    $error = array();
                    $error['msg'] = "Unknown database error";
                    $error['id'] = 4844;
                    error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);
                    
                    TDOUtil::closeDBLink($link);
                    
                    return $result;
                }
                
                foreach($taskArray as $task)
                {
                    if(empty($task['title']))
                    {
                        // Users are failing to migrate, update this to put a fake title in
                        $task['title'] = "No Title";
                        error_log("TDOLegacy::found a task with no title");
//                        $error = array();
//                        $error['id'] = 4845;
//                        $error['msg'] = "Unable to add task with no title";
//                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
//                        $result['error'] = $error;
//                        $tdoLegacy->endSyncSession($sessionId);
//                        return $result;
                    }
                    
                    if(empty($task['taskid']))
                    {
                        $error = array();
                        $error['id'] = 4846;
                        $error['msg'] = "Unable to add task with no taskid";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        $tdoLegacy->endSyncSession($sessionId);
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    if(!empty($task['parent_task']))
                        $childTasks[] = $task;
                    else
                    {
                        
                        $performAdd = true;
                        $newTask = TDOTask::getTaskForTaskId($task['taskid'], $link);
                        if($newTask == false)
                            $newTask = new TDOTask();
                        else
                        {
                            //error_log("Task already exists, updating task...");
                            $performAdd = false;
                        }
                        //$newTask = new TDOTask();
                        
                        if(TDOLegacy::updateValuesFromLegacyData($newTask, $task, $userId) == false)
                        {
                            $error = array();
                            $error['id'] = 4847;
                            $error['msg'] = "Failure to update task with values: ".$task['title'];
                            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                            $result['error'] = $error;
                            $tdoLegacy->endSyncSession($sessionId);
                            
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            
                            return $result;
                        }
                        
                        //Bug 7226 - Check to see if the note is too large
                        if(TDOTask::noteIsTooLarge($newTask->note()))
                        {
                            error_log("TDOLegacy Attempting to add an oversized note");
                            
                            $error = array();
                            $error['id'] = 4864;
                            $error['msg'] = "Note is too large for task: ".$newTask->name();
                            $result['error'] = $error;
                            $tdoLegacy->endSyncSession($sessionId);
                        
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                        
                            return $result;
                        }
                    
                        
                        if($performAdd == true)
                            $opRC = $newTask->addObject($link);
                        else
                            $opRC = $newTask->updateObject($link);
                        
                        if($opRC == false)
                        {
                            $retryCount = 0;
                            
                            while( ($retryCount < 5) && ($opRC == false) )
                            {
                                error_log("TDOLegacy::migrateUser newTask add or update failed, trying again");

                                $retryCount++;
                                
                                if($performAdd == true)
                                    $opRC = $newTask->addObject($link);
                                else
                                    $opRC = $newTask->updateObject($link);
                            }
                            
                            if($opRC == false)
                            {
                                $error = array();
                                $error['id'] = 4848;
                                $error['msg'] = "Failure to add new task: ".$task['title'];
                                error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                                $result['error'] = $error;
                                $tdoLegacy->endSyncSession($sessionId);
                                
                                mysql_query("ROLLBACK", $link);
                                TDOUtil::closeDBLink($link);
                                return $result;
                            }
                        }

                        $tasksMigrated[] = $task;

                        if(isset($task['contextid']))
                        {
                            if($task['contextid'] != "0")
                            {
                                TDOContext::assignTaskToContext($newTask->taskId(), $task['contextid'], $userId, $link);
                            }
                        }
                        
                        if(isset($task['tag']))
                        {
                            TDOTag::removeAllTagsFromTask($newTask->taskId());
                            
                            $tagValues = explode(",", $task['tag']);
                            
                            foreach($tagValues as $tagValue)
                            {
                                TDOTag::addTagNameToTask($tagValue, $newTask->taskId(), $link);
                            }
                        }
                        
                        TDOChangeLog::addChangeLog($newTask->listId(), $userId, $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);
                    }
                }
                
                if(!mysql_query("COMMIT", $link))
                {
                    $error = array();
                    $error['msg'] = "Unknown database error";
                    $error['id'] = 4849;
                    error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);

                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
            }
            else
            {
                // if we didn't get any tasks just move on
                $hasMore = false;
                continue;
            }
        }
        
        if(!mysql_query("START TRANSACTION", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4850;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);
            
            TDOUtil::closeDBLink($link);
            
            return $result;
        }
        
        // go through child tasks and add them
        foreach($childTasks as $task)
        {
            $parentTask = TDOTask::getTaskForTaskId($task['parent_task'], $link);
            if(empty($parentTask))
            {
                // Todo online has a problem here that many times we'll run into subtasks that
                // don't have a parent task.  We need to quietly fail here and move on.
                continue;
//                $error = array();
//                $error['id'] = 4851;
//                $error['msg'] = "Failure to update task with values: ".$task['title'];
//                error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
//                $result['error'] = $error;
//                $tdoLegacy->endSyncSession($sessionId);
//                return $result;
            }
            
            
            if($parentTask->isProject())
            {
                $performAdd = true;
                $newTask = TDOTask::getTaskForTaskId($task['taskid']);
                if($newTask == false)
                    $newTask = new TDOTask();
                else
                {
                    //error_log("Task already exists, updating task...");
                    $performAdd = false;
                }
                //$newTask = new TDOTask();
                
                if(TDOLegacy::updateValuesFromLegacyData($newTask, $task, $userId) == false)
                {
                    $error = array();
                    $error['id'] = 4852;
                    $error['msg'] = "Failure to update task with values: ".$task['title'];
                    error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);
                    
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
                
                //Bug 7226 - Check to see if the note is too large
                if(TDOTask::noteIsTooLarge($newTask->note()))
                {
                    error_log("TDOLegacy Attempting to add an oversized note");
                        
                    $error = array();
                    $error['id'] = 4864;
                    $error['msg'] = "Note is too large for task: ".$newTask->name();
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);
                
                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                
                    return $result;
                }
                
                
                if($performAdd == true)
                    $opRC = $newTask->addObject($link);
                else
                    $opRC = $newTask->updateObject($link);
                
                if($opRC == false)
                {
                    $retryCount = 0;
                    
                    while( ($retryCount < 5) && ($opRC == false) )
                    {
                        error_log("TDOLegacy::migrateUser newTask add or update failed, trying again");

                        $retryCount++;
                        
                        if($performAdd == true)
                            $opRC = $newTask->addObject($link);
                        else
                            $opRC = $newTask->updateObject($link);
                    }
                    
                    if($opRC == false)
                    {
                        $error = array();
                        $error['id'] = 4853;
                        $error['msg'] = "Failure to add new subtask task: ".$task['title'];
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        $tdoLegacy->endSyncSession($sessionId);
                        
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                }
                
                $tasksMigrated[] = $task;
                TDOChangeLog::addChangeLog($newTask->listId(), $userId, $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);

                if(isset($task['contextid']))
                {
                    if($task['contextid'] != "0")
                    {
                        TDOContext::assignTaskToContext($newTask->taskId(), $task['contextid'], $userId, $link);
                    }
                }

                if(isset($task['tag']))
                {
                    $tagValues = explode(",", $task['tag']);
                    
                    foreach($tagValues as $tagValue)
                    {
                        TDOTag::addTagNameToTask($tagValue, $newTask->taskId(), $link);
                    }
                }
                

                TDOTask::fixupChildPropertiesForTask($parentTask, true, $link);

            }
            else if($parentTask->isChecklist())
            {
                $performAdd = true;
                $newTaskito = TDOTaskito::taskitoForTaskitoId($task['taskid'], $link);
                if($newTaskito == false)
                    $newTaskito = new TDOTaskito();
                else
                {
                    //error_log("Taskito already exists, updating taskito...");
                    $performAdd = false;
                }
                //$newTaskito = new TDOTaskito();
                
                $newTaskito->setName($task['title']);
                $newTaskito->setTaskitoId($task['taskid']);

                if(!empty($task['completeddate']))
                {
                    $reset = date_default_timezone_get();
                    date_default_timezone_set('GMT');

                    $uniDate = strtotime($task['completeddate']);
                    $newTaskito->setCompletionDate($uniDate);

                    date_default_timezone_set($reset);
                }

                if(!empty($task['sort_order']))
                {
                    $newTaskito->setSortOrder($task['sort_order']);
                }
                
                $newTaskito->setParentId($task['parent_task']);
                
                if($performAdd == true)
                    $opRC = $newTaskito->addObject($link);
                else
                    $opRC = $newTaskito->updateObject($link);
                
                if($opRC == false)
                {
                    $retryCount = 0;
                    
                    while( ($retryCount < 5) && ($opRC == false) )
                    {
                        $retryCount++;
                        
                        error_log("TDOLegacy::migrateUser newTaskito add or update failed, trying again");

                        if($performAdd == true)
                            $opRC = $newTaskito->addObject($link);
                        else
                            $opRC = $newTaskito->updateObject($link);
                    }
                    
                    if($opRC == false)
                    {
                        $error = array();
                        $error['id'] = 4854;
                        $error['msg'] = "Failure to add new taskito: ".$task['title'];
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        $tdoLegacy->endSyncSession($sessionId);
                        
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                }
                
                $tasksMigrated[] = $task;
                TDOChangeLog::addChangeLog($parentTask->listId(), $userId, $newTaskito->taskitoId(), $newTaskito->name(), ITEM_TYPE_TASKITO, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);

                // CRG - this is for projects only, it messes up checklists because checklists don't have real subtasks
                //TDOTask::fixupChildPropertiesForTask($parentTask);
            }
        }
        
        
        if(!mysql_query("COMMIT", $link))
        {
            $error = array();
            $error['msg'] = "Unknown database error";
            $error['id'] = 4855;
            error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
            $result['error'] = $error;
            $tdoLegacy->endSyncSession($sessionId);

            mysql_query("ROLLBACK", $link);
            TDOUtil::closeDBLink($link);
            return $result;
        }
        
        
        $result['tasks_migrated'] = $tasksMigrated;

        error_log("Migrating notifications...");
        
        $notificationsMigrated = array();

        $hasMore = true;
        $serverOffset = NULL;
        
        while($hasMore)
        {
            //error_log("Looping to get more notifications...");
            
            $response = $tdoLegacy->getNotifications($sessionId, $sharedSecret, $serverOffset);
            
            if(!empty($response['error']))
            {
                $error = $response['error'];
                if( ($error['id'] == 1) || ($error['id'] == 2) || ($error['id'] == 4801) || ($error['id'] == 48022))
                {
                    error_log("Todo Online Session timed out, renewing session to continue....");
                    
                    $response = $tdoLegacy->startSyncSession($userId, ACS_MIGRATION_DEVICE);
                    
                    if(!empty($response['error']))
                    {
                        $result['error'] = $error;
                        error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    if(empty($response['session']) || empty($session['sessionid']) || empty($session['sharedSecret']))
                    {
                        $error = array();
                        $error['id'] = 4856;
                        $error['msg'] = "Session timed out and unable to get new session from legacy Todo Online";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    $session = $response['session'];
                    $sessionId = $session['sessionid'];
                    $sharedSecret = $session['sharedSecret'];
                    
                    $response = $tdoLegacy->getNotifications($sessionId, $sharedSecret, $serverOffset);
                    if(!empty($response['error']))
                    {
                        $result['error'] = $error;
                        error_log("TDOLegacy::migrateUser failed makeing the startSyncSession call with error: " . $error['msg']);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                }
                else
                {
                    $result['error'] = $error;
                    error_log("TDOLegacy::migrateUser failed makeing the getNotifications call with error: " . $error['msg']);
                    $tdoLegacy->endSyncSession($sessionId);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
            }
            
            if(!empty($response['notifications']))
            {
                $notifications = $response['notifications'];
                
                if(empty($notifications['notification']))
                {
                    $hasMore = false;            
                    continue;
                }
                
                if(isset($notifications['offset']))
                    $serverOffset = $notifications['offset'];
                
                $numReturned = $notifications['numreturned'];
                
                if($numReturned < 500)
                    $hasMore = false;
                
                
                $notificationArray = $notifications['notification'];
                // Moki really bunged stuff up here so check if there is a title key
                // and if there is, put the values inside an array so processing is the
                // same for one item or multiple items
                if(array_key_exists('uid', $notificationArray) == true)
                {
                    $notificationArray = array();
                    $notificationArray[] = $notifications['notification'];
                }
                
                if(!mysql_query("START TRANSACTION", $link))
                {
                    $error = array();
                    $error['msg'] = "Unknown database error";
                    $error['id'] = 4857;
                    error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);
                    
                    TDOUtil::closeDBLink($link);
                    
                    return $result;
                }
                
                foreach($notificationArray as $notification)
                {
                    if(empty($notification['uid']))
                    {
                        $error = array();
                        $error['id'] = 4858;
                        $error['msg'] = "Unable to add notification with no id";
                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                        $result['error'] = $error;
                        $tdoLegacy->endSyncSession($sessionId);
                        mysql_query("ROLLBACK", $link);
                        TDOUtil::closeDBLink($link);
                        return $result;
                    }
                    
                    if(empty($notification['taskid']))
                    {
                        error_log("TDOLegacy::migrateUser found a notification with no taskid, skipping");
                        continue;

//                        $error = array();
//                        $error['id'] = 4859;
//                        $error['msg'] = "Unable to add notification with no taskid";
//                        error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
//                        $result['error'] = $error;
//                        $tdoLegacy->endSyncSession($sessionId);
//                        return $result;
                    }
                 
                    $performAdd = true;
                    $newNotification = TDOTaskNotification::getNotificationForNotificationId($notification['uid'], $link);
                    if($newNotification == false)
                        $newNotification = new TDOTaskNotification();
                    else
                    {
                        //error_log("Notification already exists, updating notification...");
                        $performAdd = false;
                    }
                    //$newNotification = new TDOTaskNotification();
                    
                    $newNotification->setNotificationId($notification['uid']);
                    
                    $aTask = TDOTask::getTaskForTaskId($notification['taskid'], $link);
                    if(empty($aTask))
                    {
                        error_log("a notification could not find the task it was associated with");
                        continue;
                    }

                    $newNotification->setTaskId($notification['taskid']);
                    
                    $newNotification->setSoundName("none");
                    if(isset($notification['soundname']))
                    {
                        switch($notification['soundname'])
                        {
                            case "Morse.caf":
                                $newNotification->setSoundName("morse");
                                break;
                            case "Data.caf":
                                $newNotification->setSoundName("data");
                                break;
                            case "Bells.caf":
                                $newNotification->setSoundName("bells");
                                break;
                            case "Flute.caf":
                                $newNotification->setSoundName("flute");
                                break;
                        }
                    }

                    if(isset($notification['triggerdate']))
                    {
                        $reset = date_default_timezone_get();
                        date_default_timezone_set('GMT');

                        $uniDate = strtotime($notification['triggerdate']);
                        $newNotification->setTriggerDate($uniDate);

                        date_default_timezone_set($reset);
                    }

                    
                    if(isset($notification['triggeroffset']))
                        $newNotification->setTriggerOffset((int)$notification['triggeroffset']);

                    
                    if($performAdd == true)
                        $opRC = $newNotification->addTaskNotification($link);
                    else
                        $opRC = $newNotification->updateTaskNotification($link);
                    
                    if($opRC == false)
                    {
                        $retryCount = 0;
                        
                        while( ($retryCount < 5) && ($opRC == false) )
                        {
                            error_log("TDOLegacy::migrateUser newNotification add or update failed, trying again");
                            
                            $retryCount++;
                            
                            if($performAdd == true)
                                $opRC = $newNotification->addTaskNotification($link);
                            else
                                $opRC = $newNotification->updateTaskNotification($link);
                        }
                        
                        if($opRC == false)
                        {
                            $error = array();
                            $error['id'] = 4860;
                            $error['msg'] = "Failure to add new notification";
                            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
                            $result['error'] = $error;
                            $tdoLegacy->endSyncSession($sessionId);
                            
                            mysql_query("ROLLBACK", $link);
                            TDOUtil::closeDBLink($link);
                            return $result;
                        }
                    }
                    
                    $notificationsMigrated[] = $notification;
                    TDOChangeLog::addChangeLog($aTask->listId(), $userId, $newNotification->notificationId(), $aTask->name(), ITEM_TYPE_NOTIFICATION, CHANGE_TYPE_ADD, CHANGE_LOCATION_MIGRATION, NULL, NULL, NULL, NULL, $link);
                }
                
                if(!mysql_query("COMMIT", $link))
                {
                    $error = array();
                    $error['msg'] = "Unknown database error";
                    $error['id'] = 4861;
                    error_log("TDOLegacy::migrateUser failed with error: ".mysql_error());
                    $result['error'] = $error;
                    $tdoLegacy->endSyncSession($sessionId);

                    mysql_query("ROLLBACK", $link);
                    TDOUtil::closeDBLink($link);
                    return $result;
                }
                
            }
            else
            {
                // if we didn't get any notifications just move on
                $hasMore = false;
                continue;
            }
        }
        
        
        $result['notifications_migrated'] = $notificationsMigrated;
        
        
        $response = $tdoLegacy->endSyncSession($sessionId);


        //NCB - We already have a db link now since we're creating transactions for everything
//        $link = TDOUtil::getDBLink();
//        if(!$link) 
//        {
//            $error = array();
//            $error['id'] = 4862;
//            $error['msg'] = "Error linking to Database";
//            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
//            $result['error'] = $error;
//            return $result;
//        }
        
        
        $migrationDate = time();
		$sqlResult = mysql_query("UPDATE tdo_user_migrations SET migration_completion_date=$migrationDate, password=NULL WHERE userid='$userid'");
		if(!$sqlResult)
		{
            $error = array();
            $error['id'] = 4863;
            $error['msg'] = "Unable to mark migration users for migration: " . mysql_error();
            error_log("TDOLegacy::migrateUser failed with error: " . $error['msg']);
            $result['error'] = $error;
            TDOUtil::closeDBLink($link);
            return $result;
        }
        
        // TODO: do somthing with the result?
        
        return $result;
    }
    
    public static function updateValuesFromLegacyData($newTask, $legacyTask, $userId)
    {
        if(empty($legacyTask['title']))
            return false;

        if(empty($legacyTask['taskid']))
            return false;
        
        $newTask->setName($legacyTask['title']);
        $newTask->setTaskId($legacyTask['taskid']);

        
//        if(strcmp($legacyTask['title'], "Prepare to migrate data") == 0)
//        {
//            error_log("*********              Migrating the checlist, what is wrong with it?");
//            var_dump($legacyTask);
//        }
        
        
        // we need to set the task type high in the stack so everything else will fix up correctly
        if(!empty($legacyTask['type']))
        {
			$newTask->setTaskType($legacyTask['type']);
        }
        
        if(!empty($legacyTask['type_data']))
        {
//            $newTypeData = TDOLegacy::convertLegacyTaskTypeData($legacyTask['type_data']);
            
            if(!empty($newTypeData))
                $newTask->setTypeData($newTypeData);
        }
        
        if(!empty($legacyTask['listid']))
        {
            $list = TDOList::getListForListid($legacyTask['listid']);
            if(!empty($list))
            {
                $newTask->setListId($legacyTask['listid']);
            }
            else
            {
                //error_log("Couldn't find list for task, putting in user inbox");
                $listid = TDOList::getUserInboxId($userId, false);
                $newTask->setListId($listid);
            }
        }
        else
        {
            $listid = TDOList::getUserInboxId($userId, false);
            $newTask->setListId($listid);
        }
        
        if(!empty($legacyTask['parent_task']))
            $newTask->setParentId($legacyTask['parent_task']);
        
        if(!empty($legacyTask['duedate']))
        {
            $reset = date_default_timezone_get();
            date_default_timezone_set('GMT');
            
            $uniDate = strtotime($legacyTask['duedate']);

            if(!empty($uniDate))
            {
                $dueDateHasTime = false;
                if(isset($legacyTask['has_due_time']) && $legacyTask['has_due_time'] != 0)
                {
                    $dueDateHasTime = true;
                }
                
                $newTask->setDueDateHasTime($dueDateHasTime);
                if(!$dueDateHasTime)
                {
                    $uniDate = TDOUtil::normalizedDateFromGMT($uniDate);
                }

                $newTask->setDueDate($uniDate);
            }
            else
            {
                $newTask->setDueDateHasTime(false);
            }
            
            
            date_default_timezone_set($reset);
        }
        
        if(!empty($legacyTask['completeddate']))
        {
            $reset = date_default_timezone_get();
            date_default_timezone_set('GMT');
            
            $uniDate = strtotime($legacyTask['completeddate']);
            $newTask->setCompletionDate($uniDate);

            date_default_timezone_set($reset);
        }
        
        if(!empty($legacyTask['lastupdated']))
        {
            $reset = date_default_timezone_get();
            date_default_timezone_set('GMT');
            
            $uniDate = strtotime($legacyTask['lastupdated']);
            $newTask->setTimestamp($uniDate);

            date_default_timezone_set($reset);
        }

        if(!empty($legacyTask['starred']))
        {
            $newTask->setStarred($legacyTask['starred']);
        }

        if(!empty($legacyTask['priority']))
        {
            switch($legacyTask['priority'])
            {
                case 3:
                    $newTask->setPriority(1);
                    break;
                case 2:
                    $newTask->setPriority(5);
                    break;
                case 1:
                    $newTask->setPriority(9);
                    break;
                default:
                    $newTask->setPriority(0);
                    break;
            }
        }

        if(!empty($legacyTask['notes']))
        {
            $newTask->setNote($legacyTask['notes']);
        }


        if(!empty($legacyTask['repeat']))
        {
			$newTask->setRecurrenceType($legacyTask['repeat']);
        }

        if(!empty($legacyTask['rep_advanced']))
        {
			$newTask->setAdvancedRecurrenceString($legacyTask['rep_advanced']);
        }

        if(!empty($legacyTask['sort_order']))
        {
			$newTask->setSortOrder($legacyTask['sort_order']);
        }

        return true;
    }
	
	
	// This method is used in the admin interface to help us know information
	// about migrated users.
	public static function getMigrationInfoForUser($userid)
	{
		if (empty($userid))
			return false;
		
        $link = TDOUtil::getDBLink();
        if(!$link)
            return false;
		
		$userid = mysql_real_escape_string($userid, $link);
		
		$sql = "SELECT migration_completion_date,migration_last_attempt,daemonid FROM tdo_user_migrations WHERE userid='$userid'";
        
        $result = mysql_query($sql, $link);
		if(!$result)
		{
            error_log("TDOLegacy::getMigrationInfoForUser($userid) failed with error: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }
		
		$migrationInfo = array();
		if($row = mysql_fetch_array($result))
		{
			if (isset($row['migration_completion_date']))
				$migrationInfo['completionDate'] = $row['migration_completion_date'];
			
			if (isset($row['migration_last_attempt']))
				$migrationInfo['lastAttempt'] = $row['migration_last_attempt'];

			if (isset($row['daemonid']) && ($row['daemonid'] == "FAILED") )
            {
				$migrationInfo['failed'] = true;
            }
		}
		else
		{
			return false;
		}
		
        return $migrationInfo;
	}
    
    
    public static function convertLegacyTaskTypeData($taskTypeData)
    {
        $typeArray = explode("\n", $taskTypeData);
        $lines = array();
        
        foreach($typeArray as $typeLine)
        {
            $line = trim($typeLine);
            
            if(empty($line))
                continue;
            
            if(strncmp($line, "----", 4) == 0)
                continue;
            
            if(strncmp($line, "contact:", 8) == 0)
                continue;
            
            if(strncmp($line, "name:", 5) == 0)
                continue;
            
            $lines[] = $line;
        }
        
        //        echo $taskTypeData . "\n";
        //        var_dump($lines);
        
        $actionValue = "";
        
        foreach($lines as $line)
        {
            $pos = strpos($line, ":");
            
            if( ($pos > 0) && (strncmp($line, "htt", 3) != 0) )
            {
                $value = substr($line, $pos+1);
                $actionValue .= trim($value) . "\n";
            }
            else
                $actionValue .= $line . "\n";
        }
        
        return $actionValue;
    }    
    
    

}
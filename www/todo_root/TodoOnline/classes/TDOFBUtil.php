<?php
//      TDOUtil
//      Used to handle all user data

// include files

class TDOFBUtil
{
    public static function removeFacebookRequest($requestId, $fbUserId)
    {
        global $fb_app_id, $fb_secret;
    
        $accessToken = $fb_app_id.'|'.$fb_secret;
        $requestId = $requestId."_".$fbUserId;
        $delete_url = "https://graph.facebook.com/$requestId?access_token=$accessToken&method=DELETE";
        $result = TDOFBUtil::curl_get_file_contents($delete_url);
        if($result == false)
        {
            error_log("failed to delete facebook request");
        }
        
        return $result;
    }

    public static function getFBUserNameForFBUserId($fbUserId)
    {
        global $facebook;
        // get facebook name of user being removed from invitation for logging
        try 
        {
            $fql = 'SELECT name from user where uid = ' . $fbUserId;
            $ret_obj = $facebook->api(array(
                                                    'method' => 'fql.query',
                                                    'query' => $fql,
                                                    ));
            $invitedName = $ret_obj[0]['name'];
                    
        } catch(FacebookApiException $e) 
        {
            $invitedName = $fbUserId;
            error_log("Unable to fetch facebook user name :".$e->getMessage());
        }
        
        return $invitedName;
    }
    
    //this is from https://developers.facebook.com/blog/post/2011/05/13/how-to--handle-expired-access-tokens/
    public static function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        $err  = curl_getinfo($c,CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents) return $contents;
        else return FALSE;
    }

}
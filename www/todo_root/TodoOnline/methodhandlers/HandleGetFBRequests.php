<?php
	
	
if($method == "getPendingFBRequestsForUser")
{
    $fbId = TDOUser::facebookIdForUserId($session->getUserId());
    $accessToken = $fb_app_id.'|'.$fb_secret;
        
    if(empty($fbId))
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Could not find Facebook id. Is your account linked to Facebook?'),
        ));
        return;
    }
        
    $responseArray = json_decode(file_get_contents("https://graph.facebook.com/$fbId/apprequests?access_token=$accessToken"), true);

    if(empty($responseArray) || !isset($responseArray['data']))
    {
        echo json_encode(array(
            'success' => FALSE,
            'error' => _('Error getting request data from Facebook'),
        ));
        return;
    }
    
    $responseData = $responseArray['data'];
    
    $jsonRequests = array();
    foreach($responseData as $requestData)
    {
        if(isset($requestData['id']))
        {
            $jsonRequest = array();
        
            $requestId = $requestData['id'];
            $reqComps = explode("_", $requestId);
            $requestId = $reqComps['0'];
            
            $jsonRequest['requestid'] = $requestId;
            
            $fromUserId = $requestData['from']['id'];
            $fromUserName = $requestData['from']['name'];
            $pictureURL = "https://graph.facebook.com/$fromUserId/picture";
            
            $jsonRequest['imgurl'] = $pictureURL;
            $jsonRequest['fromusername'] = $fromUserName;
            
            $shareInvitation = TDOInvitation::getInvitationForRequestIdAndFacebookId($requestId, $fbId);
            if(!empty($shareInvitation))
            {
                $jsonRequest['invitationid'] = $shareInvitation->invitationId();
                $sharelistid = $shareInvitation->listId();
                $listName = TDOList::getNameForList($sharelistid);
                if(!empty($listName))
                {
                    $jsonRequest['listname'] = $listName;
                }
            }
            
            $jsonRequests[] = $jsonRequest;
        }
    }
    
    $jsonResponse = array();
    $jsonResponse['success'] = true;
    $jsonResponse['requests'] = $jsonRequests;
    
    echo json_encode($jsonResponse);

}

	
    
?>


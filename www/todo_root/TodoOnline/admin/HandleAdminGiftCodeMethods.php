<?php

include_once('TodoOnline/base_sdk.php');


if ($method == "getAllGiftCodes")
{
	// PARAMETERS:
	//	offset		(optional, defaults to 0)
	//	limit		(optional, defaults to 50, max of 100)
    //  used        set this to get only used gift codes. Otherwise, will return only unused gift codes.
	$offset = 0;
	$limit = 50;
	
	if (isset($_POST['offset']))
		$offset = $_POST['offset'];
	
	if (isset($_POST['limit']))
		$limit = $_POST['limit'];
    
    $used = false;
    if(isset($_POST['used']))
        $used = true;
    
    $giftCodes = TDOGiftCode::allGiftCodesInSystem(!$used, $used, $offset, $limit);
    
    if($giftCodes === false)
    {
        error_log("HandleAdminGiftCodeMethods failed to get gift codes");
        echo '{"success":false}';
        return;
    }
    
    $giftCodesJSON = array();
    foreach($giftCodes as $giftCode)
    {
        $codeJSON = $giftCode->getPropertiesArray(true);
        $giftCodesJSON[] = $codeJSON;
    }
    
    $count = TDOGiftCode::countGiftCodesInSystem(!$used, $used);
    if($count === false)
    {
        error_log("HandleAdminGiftCodeMethods failed to get gift code count");
        echo '{"success":false}';
        return;       
    }
	
    $resultArray = array();
    $resultArray['success'] = true;
    $resultArray['gift_codes'] = $giftCodesJSON;
    $resultArray['gift_code_count'] = $count;
    
	echo json_encode($resultArray);
}


?>

<?php

include_once('TodoOnline/base_sdk.php');


if ($method == "listPromoCodes")
{
	// PARAMETERS:
	//	offset		(optional, defaults to 0)
	//	limit		(optional, defaults to 50, max of 100)
	$offset = 0;
	$limit = 50;
	
	if (isset($_POST['offset']))
		$offset = $_POST['offset'];
	
	if (isset($_POST['limit']))
		$limit = $_POST['limit'];
	
	$promoCodes = TDOPromoCode::listPromoCodes($offset, $limit);
	$jsonPromoCodes = json_encode($promoCodes);
	echo $jsonPromoCodes;
}
else if ($method == "listUsedPromoCodes")
{
	// PARAMETERS:
	//	offset		(optional, defaults to 0)
	//	limit		(optional, defaults to 50, max of 100)
	$offset = 0;
	$limit = 50;
	
	if (isset($_POST['offset']))
		$offset = $_POST['offset'];
	
	if (isset($_POST['limit']))
		$limit = $_POST['limit'];
	
	$promoCodes = TDOPromoCode::listUsedPromoCodes($offset, $limit);
	$jsonPromoCodes = json_encode($promoCodes);
	echo $jsonPromoCodes;
}
else if ($method == "createPromoCode")
{
	// PARAMETERS: (all required)
	//	numberOfMonths
	//		The number of months the promo code will be good for.  This
	//		value MUST be between 1 and 12.
	//	note
	//		An explanation of why the promo code is being created.  This
	//		method will fail if the note is blank or seemingly too small.
	//
	// RETURNS:
	//	Returns an array with the following keys/values:
	//
	//	SUCCESS:
	//		"success"	=> true
	//		"promocode" => "Newly generated promo code"
	//		"promolink" => "Clickable link a user can use to redeem the promo code"
	//
	//	ERROR:
	//		"errcode"	=> <numeric error code>
	//		"errdesc"	=> "Description of the error"
	
	if (!isset($_POST['numberOfMonths']))
	{
		error_log("HandlePromoCodeMethods::createPromoCode called and missing a required parameter: numberOfMonths");
		echo '{"success":false}';
		return;
	}
	
	if (!isset($_POST['note']))
	{
		error_log("HandlePromoCodeMethods::createPromoCode called and missing a required parameter: note");
		echo '{"success":false}';
		return;
	}
	
	$numberOfMonths	= (int)$_POST['numberOfMonths'];
	$creatorUserID	= $session->getUserId();
	$note			= $_POST['note'];
	
	$result = TDOPromoCode::createPromoCode($numberOfMonths, NULL, $creatorUserID, $note);
	$jsonResult = json_encode($result);
	echo $jsonResult;
}
else if ($method == "deletePromoCode")
{
	// PARAMETERS: (all required)
	//	promoCode
	//		THE promo code. :)
	//
	// RETURNS:
	//
	//	SUCCESS:
	//		"success"	=> true
	//
	//	ERROR:
	//		"errcode"	=> <numeric error code>
	//		"errdesc"	=> "Description of the error"
	
	if (!isset($_POST['promoCode']))
	{
		error_log("HandlePromoCodeMethods::deletePromoCode called and missing a required parameter: promoCode");
		echo '{"success":false}';
		return;
	}
	
	$promoCode = $_POST['promoCode'];
	
	$result = TDOPromoCode::deletePromoCode($promoCode);
	$jsonResult = json_encode($result);
	echo $jsonResult;
}
    
?>

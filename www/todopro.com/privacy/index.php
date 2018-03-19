<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<?php
	
	include_once('TodoOnline/base_sdk.php');
	
	$requestURI = $_SERVER['REQUEST_URI'];
	$externalFile = NULL;
	$pageTitle = NULL;
	
	// This next line is for making this work on our development machines. The
	// S3 base URL is blank on our development machines but is not on the
	// development/production server.
	$baseURL = '../';
	$pathPrefix = '../';
	if (strlen(SITE_BASE_S3_URL) > 0)
	{
		$baseURL = SITE_BASE_S3_URL;
		$pathPrefix = '';
	}
	
	if (strstr($requestURI, 'privacy'))
	{
        if (DEFAULT_LOCALE_IN_USE != DEFAULT_LOCALE) {
            $externalFile = 'html/' . mb_strtolower(DEFAULT_LOCALE_IN_USE) . '_privacy.html';
        } else {
            $externalFile = 'html/privacy.html';
        }
		$pageTitle = _('Privacy Policy');
	}
	else if (strstr($requestURI, 'terms'))
	{
        if (DEFAULT_LOCALE_IN_USE != DEFAULT_LOCALE) {
            $externalFile = 'html/' . mb_strtolower(DEFAULT_LOCALE_IN_USE) . '_terms.html';
        } else {
		    $externalFile = 'html/terms.html';
        }
		$pageTitle = _('Terms of Service');
	}
	else if (strstr($requestURI, 'unsupportedBrowser'))
	{
        if (DEFAULT_LOCALE_IN_USE != DEFAULT_LOCALE) {
            $externalFile = 'html/' . mb_strtolower(DEFAULT_LOCALE_IN_USE) . '_unsupportedBrowser.html';
        } else {
            $externalFile = 'html/unsupportedBrowser.html';
        }
		$pageTitle = _('Unsupported Browser');
	}
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
<head>
<title id="page_title">Todo Cloud - <?php echo $pageTitle; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo $pathPrefix . TP_CSS_PATH_BASE; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $pathPrefix . TP_CSS_PATH_STYLE; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $pathPrefix . TP_CSS_PATH_APP_SETTINGS; ?>" />
<link rel="stylesheet" type="text/css" media="print" href="<?php echo $pathPrefix . TP_CSS_PATH_PRINT_STYLE; ?>" />
<link rel="shortcut icon" href="<?php echo $pathPrefix . TP_IMG_PATH_FAV_ICON; ?>" type="image/x-icon" />
<script type="text/javascript" src="<?php echo $pathPrefix . TP_JS_PATH_LANG; ?>"></script>
<script type="text/javascript" src="<?php echo $pathPrefix . TP_JS_PATH_UTIL_FUNCTIONS; ?>" ></script>

</head>
<body>

<script type="text/javascript" charset="utf-8">
	var xmlhttp;
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open('GET', '<?php echo $baseURL . $externalFile; ?>', false);
	xmlhttp.send();
	
	var content = xmlhttp.responseText;
	
	var html = '	<style>html{overflow:auto}.marketing_content > * {margin-left:20px;margin-right:20px} body{overflow: auto;}</style>';
	
		html += '	<div class="landing_header_wrap">';
		html += '	   	 	<div class="landing_header">';
		html += '		 		<a href="../" ><div class="app_logo sign_in_view"></div></a>';
		html += '	   		</div>';
		html += '	</div>';	
		html += '	<div class="marketing_content_wrap" style="width:950px;margin:0 auto;display:block">';
		html += '		<div class="marketing_content" style="width:100%;height:auto;position:relative;top:6px;margin-top:10px">' + content + '</div>';
		html += '		<div class="marketing_content dropshadow left"></div>';
		html += '		<div class="marketing_content dropshadow right" style="right:-15px"></div>';
		html += '	</div><br/><br/>';
		
	document.write(html);	
</script>
	
	
</body>
</html>

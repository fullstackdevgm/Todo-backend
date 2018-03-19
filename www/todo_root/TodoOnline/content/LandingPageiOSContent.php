<?php
	
	include_once('TodoOnline/base_sdk.php');
	
	$html = '';
	$html .= '<div class="ios_landing_content">';
	$html .= '	<div class="ios_landing_header header">';
							//logo
	$html .= '		<a href="." style="position:relative;top:6px"><div class="app_logo sign_in_view_ios"></div></a>';
    if($session->isLoggedIn())
    {
       $html .= '  <a href="?method=logout" style="float:right;margin-top:-10px;margin-right:3px;">Log out</a>';
    }
	$html .= '	</div>';
	
//	$html .= '		<div class="ios_landing_video" id="video_iframe_wrap" style="border:1px solid rgb(70,70,70)">';
//	$html .= '			<iframe style="width:100%;height:100%" src="http://www.youtube.com/embed/reY86FOo5F0?rel=0" frameborder="0" allowfullscreen></iframe>';
//	$html .= '		</div>';
	$html .= '		<br/>';
	$html .= '		<div class="iphone_table_view">';
	$html .= '			<div class="ios_row" id="ios_row1">';
	$html .= '				<div class="ios_app_logo">';
	$html .= '				</div>';
	$html .= '					<a href="https://itunes.apple.com/us/app/id568428364?mt=8">Install Todo Cloud</a>';
	$html .= '			</div><br/>';
	$html .= '			<div class="ios_row" id="ios_row1">';
	$html .= '				<div class="ios_app_logo">';
	$html .= '				</div>';
	$html .= '					<a href="appigotodo://">' . _('Launch Todo Cloud') . '</a>';
	$html .= '			</div>';
	$html .= '		</div>';
	$html .= '   	<div id="landing_footer" class="landing_footer" style="padding-bottom:20px"></div>';
	$html .= '</div>';
	$html .= '<style>body{min-width:0px;height:100%;width:100%}</style>';
	$html .= '<script>catchOrientationChange();document.getElementById(\'landing_footer\').innerHTML = getFooterLinksHtml();</script>';


    echo $html;
	
?>

<?php


if(isset($_COOKIE['TodoOnlineContextId']))
{
    $contextid = $_COOKIE['TodoOnlineContextId'];
    $context = TDOContext::getContextForContextid($contextid);
    if(!empty($context))
    {
        $userid = $session->getUserId();
		if($userid == $context->getUserId())
        {
            $contextName = $context->getName();
            //print link
            print '	<li class="group_option">';
            print '		<span class="option_left_icon"></span>';
            print '		<a href="javascript:displayDeleteContextModal(\''.$contextid.'\')">';
            print '			<div class="option_name" >Delete Context</div>';
            print '		</a>';
            print '	</li>';
		
        }
    }
    include_once('TodoOnline/ajax_config.html');
}
?>

<script type="text/javascript" src="<?php echo TP_JS_PATH_CONTEXT_FUNCTIONS; ?>"></script>

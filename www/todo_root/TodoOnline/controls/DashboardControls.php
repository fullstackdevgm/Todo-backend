<div id="control_container" class="control_container">

<!--Lists-->
	<input id="currentListId" type="hidden" value="<?php print isset($_COOKIE['TodoOnlineListId']) ? $_COOKIE['TodoOnlineListId']: 'all';?>"/>
	<input id="currentListName" type="hidden" value="" />
    <ul class="control_group" id="dashboard_lists_control"></ul>

    <!--Completion Status-->
    <?php
        $showCompletedTasks = 0;
        if(isset($_COOKIE['TodoOnlineShowCompletedTasks']))
        {
            $showCompletedTasks = intval($_COOKIE['TodoOnlineShowCompletedTasks']);
        }
        echo '<ul class="control_group context_group" id="dashboard_completion_control">';
        echo '<li><div class="group_title">' . _('Status') . '</div></li>';

        $activeSelectedClass = '';
        $completedSelectedClass = '';

        if($showCompletedTasks)
        {
           $completedSelectedClass = 'selected_option';
        }
        else
        {
            $activeSelectedClass = 'selected_option';
        }
        echo '<li class="group_option '.$activeSelectedClass.'" style="padding-left: 33px;" >';
        echo '  <span class="option_left_icon '.$activeSelectedClass .'" style="width: 0px">';
        echo '  </span>';
        echo '  <a href="javascript:void(0)" onclick="SetCookieAndLoad(\'TodoOnlineShowCompletedTasks\',\'0\')">';
        echo '      <div>' . _('Active') . '</div>';
        echo '  </a>';
        echo '</li>';

        echo '<li class="group_option '.$completedSelectedClass.'" style="padding-left: 33px">';
        echo '  <span class="option_left_icon '. $completedSelectedClass. '" style="width: 0px">';
        echo '  </span>';
        echo '  <a href="javascript:void(0)" onclick="SetCookieAndLoad(\'TodoOnlineShowCompletedTasks\',\'1\')">';
        echo '      <div>' . _('Completed') . '</div>';
        echo '  </a>';
        echo '</li>';

        echo '</ul>';

    ?>

	<!--Contexts-->
	<?php
		if (isset($_COOKIE['TodoOnlineContextId']))
		{
			$currentContextId = $_COOKIE['TodoOnlineContextId'];

			if ($currentContextId == 'all' || $currentContextId == 'nocontext')
				$currentContextName = $currentContextId;
			else
			{
				$currentContext = TDOContext::getContextForContextid($currentContextId);
                if(!empty($currentContext))
                    $currentContextName = $currentContext->getName();
                else
                {
                    $currentContextId = 'all';
                    $currentContextName = $currentContextId;
                }
			}
		}
		else
		{
			$currentContextId = 'all';
			$currentContextName = $currentContextId;
		}
        echo '<script type="text/javascript">var filterBannerContextName = "'.$currentContextName.'";</script>';

		echo '<input id="currentContextId" type="hidden" value="'.$currentContextId.'"/>';
	?>

	<ul class="control_group context_group" id="dashboard_contexts_control">
	</ul>


	<!--Tags-->
	<?php
		if (isset($_COOKIE['TodoOnlineTagId']))
		{
			$currentTagIds = $_COOKIE['TodoOnlineTagId'];
			$currentTagIdsArray = explode(',', $currentTagIds);
			$currentTagNames = array();

			foreach ($currentTagIdsArray as $tagId)
			{
				if ($tagId == 'all' || $tagId == 'notag')
					array_push($currentTagNames, $tagId);
				else
				{
					$tag = TDOTag::getTagForTagid($tagId);
                    if($tag)
                    {
                        $tagName = $tag->getName();
                        array_push($currentTagNames, $tagName);
                    }
				}
			}

			$currentTagNames = implode(', ', $currentTagNames);
		}
		else
		{
			$currentTagIds = 'all';
			$currentTagNames = $currentTagIds;
		}
        $currentTagNames = htmlspecialchars($currentTagNames);
		echo '<script type="text/javascript">var filterBannerTagNames = "'.$currentTagNames.'";</script>';
		echo '<input id="currentTagIds" type="hidden" value="'.$currentTagIds.'"/>';
	?>

    <ul class="control_group context_group" id="dashboard_tags_control">
	</ul>

    <!--Mobile
    <ul class="control_group">
        <li class="control_item"><div class="control_title">Mobile Setup</div></li>
        <li class="control_item"><a class="control_link" href="?mobile=true"><div class="control_option <?php print (isset($_GET['mobile'])) ? 'selected_option': '';?>">iPhone</div></a></li>
	</ul>
	-->
	<!--Sandbox
    <ul class="control_group">
        <li class="control_item"><div class="control_title">Sandbox</div></li>
        <li class="control_item"><a class="control_link" href="?bravo=argh"><div class="control_option <?php print (isset($_GET['bravo'])) ? 'selected_option': '';?>">Bravo</div></a></li>
	</ul>
	-->

</div>
	<div class="dashboard_calendar_wrap">
		<div id="dashboard_calendar" class="dashboard_calendar"></div>
	</div>


<link href="<?php echo TP_CSS_PATH_LIST_SETTINGS; ?>" type="text/css" rel="stylesheet">
<script type="text/javascript" src="<?php echo TP_JS_PATH_VERIFY_EMAIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_LIST_FUNCTIONS; ?>"></script>
<?php
include('Facebook/config.php');
echo "<script>var appid = '$fb_app_id';</script>";
echo  "<div id=\"fb-root\"></div>";
echo "<input type=\"hidden\" id=\"member_page_userid\" value=\"".$session->getUserId()."\">";

?>
<script type="text/javascript" src="<?php echo TP_JS_PATH_LIST_MEMBER_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_LIST_DELETE_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_TAG_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_CONTEXT_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_DASHBOARD_CONTROLS; ?>" ></script>

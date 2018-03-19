<div class="control_container">
	<!--List Controls-->
    <ul class="control_group">
        <li class="control_item">

		<?php
			if(isset($_COOKIE['TodoOnlineListId']))
			{
				$listName = TDOList::getNameForList($_COOKIE['TodoOnlineListId']);
				// print '<a class="control_link" href="?list='.$_GET['list'].'&showProperties=yes&members=yes">';
				if(!empty($listName))
					print '<div class="group_title">'.$listName;
				else
                    echo '<div class="group_title">' . _('List Controls');
			}
			else
			{
				print '<a class="control_link" href="">';
                echo '<div class="group_title">' . _('List Controls');
			}
		?>
               </div>
            </a>
        </li>

<?php
        if(isset($_COOKIE['TodoOnlineListId']))
        {
            $selectedlistid = $_COOKIE['TodoOnlineListId'];
        }
        else
        {
            $selectedlistid = 'all';
        }

?>
        <li class="group_option <?php print (isset($_GET['members'])) ? 'selected_option': '';?>"><span class="option_left_icon">	</span><a class="control_link" href="?showProperties=list&members=yes"><span class="option_name"><?php _e('Members'); ?></span></a></li>
		<li class="group_option <?php print (isset($_GET['listsettings'])) ? 'selected_option': '';?>"><span class="option_left_icon">	</span><a class="control_link" href="?showProperties=list&listsettings=yes"><span class="option_name"><?php _e('Settings'); ?></span></a></li>
		<li class="group_option <?php print (isset($_GET['listhistory'])) ? 'selected_option': '';?>"><span class="option_left_icon">	</span><a class="control_link" href="?showProperties=list&listhistory=yes"><span class="option_name"><?php _e('History'); ?></span></a></li>
	</ul>

	<!--List actions-->
<?php

    $userInbox = TDOList::getUserInboxId($session->getUserId(), false);
    if($selectedlistid != $userInbox)
    {
        ?>
        <ul class="control_group">
            <li class="control_item"><div class="group_title"><?php _e('Actions'); ?></div></li>
            <li class="group_option">
                <span class="option_left_icon">	</span>
                <a class="control_link" href="javascript:void(0)" onclick="getDeleteListInfo('<?php echo $selectedlistid; ?>')"><span class="option_name"><?php _e('Delete List'); ?></span></a>
            </li>
        </ul>
<?php
}

?>
<script type="text/javascript" src="<?php echo TP_JS_PATH_LIST_DELETE_FUNCTIONS; ?>" ></script>
</div>

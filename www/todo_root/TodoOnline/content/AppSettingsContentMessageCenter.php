
<div class="setting_options_container" style="padding:10px">

    <h2 style="margin-top:10px;margin-bottom:20px; display:inline-block;"><?php _e('Announcements'); ?></h2>
    <div class="mc_buttons_wrap">
    	<span id="message_mark_button" class="msg_center button disabled"><?php _e('Mark Read'); ?></span>
    	<span id="message_delete_button" class="msg_center button disabled"><?php _e('Delete'); ?></span>
    </div>
    <div id="messages_container" class="messages_container"></div>
    <div id="messages_loading_indicator" style="display:none;margin-top:10px;"><span class="progress_indicator" style="display:inline-block;"></span> <?php _e('Loading Announcements'); ?></div>

    <span id="messages_show_more_button" class="button" style="display:none;margin-top:10px;" onclick="loadMessagesFromServer()"><?php _e('Show More'); ?></span>
</div>


<script type="text/javascript" src="<?php echo TP_JS_PATH_UTIL_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_MESSAGE_CENTER_FUNCTIONS; ?>" ></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_APP_SETTINGS_FUNCTIONS; ?>" ></script>

<script type="text/javascript">
    window.addEventListener('load', loadMessageCenterContent);
</script>
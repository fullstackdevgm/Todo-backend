<h2>System Notification</h2>

<div id="current_notification_container" style="margin:20px 0 20px 20px;">Loading current notification...</div>

<div id="add_notification_container" style="margin:40px 0 0 20px;">
    <textarea id="notification_message_textarea" placeholder="Compose a new system notification message..." style="width:400px;" onkeyup="updateNotificationButtonEnablement()"></textarea>
    <div class="button disabled" id="notification_button" style="margin-top:16px;">Post Notification</div>
    <div><input type="text" id="notification_url_textinput" placeholder="Enter a link for users to learn more..." style="width:400px;margin-top:10px;"/></div>
    

</div>

<script type="text/javascript" src="<?php echo TP_JS_PATH__SYSTEM_NOTIFICATION_FUNCTIONS; ?>"></script>

<script>

window.addEventListener('load', loadCurrentSystemNotification, false);

</script>



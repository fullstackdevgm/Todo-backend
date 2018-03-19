
<div class="breath-20"></div>
<h2>Add Message</h2>
<div id="add_message_container" style="margin:20px 0 0 20px;">
    <div><input id="en_subject_textbox" type="text" placeholder="Subject..." onkeyup="updateAddMessageButtonEnablement()"></input></div>
    <textarea id="en_message_textarea" placeholder="Html message body..." style="width:400px;margin-top:10px;" onkeyup="updateAddMessageButtonEnablement()"></textarea>

    <div id="message_languages_container"></div>

    <div id="account_based_message_details" style="margin-top:10px;display:none;">
        Send to users who have used Todo Cloud for <input type="text" id="account_duration_weeks" style="min-width:10px;" maxlength="4" size="2" onkeyup="formatPositiveInteger(this, 0, 999)" value="0" /> week(s)
    </div>

    <div id="upgrade_based_message_details" style="margin-top:10px;display:none;">
        <div style="margin-bottom:10px;">Send to users running the following versions:</div>
        <table style="display:inline-block; margin-left:10px;vertical-align:top;">
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todo" onclick="updateAddMessageButtonEnablement()" id="app_id_todo"/></td>
            <td><label for="app_id_todo">Todo</label></td>
            <td><input type="text" id="todo_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todoipad" onclick="updateAddMessageButtonEnablement()" id="app_id_todoipad"/></td>
            <td><label for="app_id_todoipad">Todo iPad</label></td>
            <td><input type="text" id="todoipad_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todomac" onclick="updateAddMessageButtonEnablement()" id="app_id_todomac"/></td>
            <td><label for="app_id_todomac">Todo for Mac</label></td>
            <td><input type="text" id="todomac_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todopro" onclick="updateAddMessageButtonEnablement()" id="app_id_todopro"/></td>
            <td><label for="app_id_todopro">Todo Cloud (iOS)</label></td>
            <td><input type="text" id="todopro_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todopromac" onclick="updateAddMessageButtonEnablement()" id="app_id_todopromac"/></td>
            <td><label for="app_id_todopromac">Todo Cloud (Mac)</label></td>
            <td><input type="text" id="todopromac_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todoproweb" onclick="updateAddMessageButtonEnablement()" id="app_id_todoproweb"/></td>
            <td><label for="app_id_todoproweb">Todo Cloud (Web)</label></td>
            <td><input type="text" id="todoproweb_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>
        <tr>
            <td><input type="checkbox" checked="checked" name="app_id" value="todoproandroid" onclick="updateAddMessageButtonEnablement()" id="app_id_todoproandroid"/></td>
            <td><label for="app_id_todoproandroid">Todo Cloud (Android)</label></td>
            <td><input type="text" id="todoproandroid_version" placeholder="Version..." style="min-width:10px;" size="4"/></td>
        </tr>

        </table>
    </div>

    <div style="margin-top:10px;">
        <span>
            <select id="message_type_picker" style="width:120px;vertical-align:top;" onchange="updateVisibleDetailsForMessageType()">
                <option value="0">System Alert</option>
                <option value="1">Upgrade Based</option>
                <option value="2">Account Duration Based</option>
            </select>
        </span>
        <span>
            <select id="message_priority_picker" style="width:100px;vertical-align:top;">
                <option value="0">No Priority</option>
                <option value="1">Low</option>
                <option value="3">Medium</option>
                <option value="5">Important</option>
                <option value="47">Urgent</option>
            </select>
        </span>
        <span>
            <table style="display:inline-block; margin-left:10px;vertical-align:top;">
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="0" onclick="updateAddMessageButtonEnablement()" id="device_type_iphone"/></td><td><label for="device_type_iphone">iPhone</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="1" onclick="updateAddMessageButtonEnablement()" id="device_type_ipod"/></td><td><label for="device_type_ipod">iPod Touch</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="2" onclick="updateAddMessageButtonEnablement()" id="device_type_ipad"/></td><td><label for="device_type_ipad">iPad</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="3" onclick="updateAddMessageButtonEnablement()" id="device_type_mac"/></td><td><label for="device_type_mac">Mac</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="4" onclick="updateAddMessageButtonEnablement()" id="device_type_web"/></td><td><label for="device_type_web">Web</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="device_type" value="5" onclick="updateAddMessageButtonEnablement()" id="device_type_android"/></td><td><label for="device_type_android">Android</label></td></tr>
            </table>
        </span>
        <span id="sync_service_options">
            <table style="display:inline-block; margin-left:10px;vertical-align:top;">
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="0" onclick="updateAddMessageButtonEnablement()" id="sync_service_todo_pro"/></td><td><label for="sync_service_todo_pro">Todo Cloud</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="1" onclick="updateAddMessageButtonEnablement()" id="sync_service_dropbox"/></td><td><label for="sync_service_dropbox">Dropbox</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="2" onclick="updateAddMessageButtonEnablement()" id="sync_service_appigo_sync"/></td><td><label for="sync_service_appigo_sync">Appigo Sync</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="3" onclick="updateAddMessageButtonEnablement()" id="sync_service_icloud"/></td><td><label for="sync_service_icloud">iCloud</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="4" onclick="updateAddMessageButtonEnablement()" id="sync_service_toodledo"/></td><td><label for="sync_service_toodledo">Toodledo</label></td></tr>
                <tr><td><input type="checkbox" checked="checked" name="sync_service" value="5" onclick="updateAddMessageButtonEnablement()" id="sync_service_none"/></td><td><label for="sync_service_none">No Sync Service</label></td></tr>
            </table>
        </span>

        <span id="expiration_date_wrap" style="display:inline-block;margin-left:10px;margin-right:10px;margin-top:4px;" class="property_wrapper">
            <div>Expires:</div>
            <div id="expiration_date_label" style="text-decoration:underline;cursor: pointer;" class="label" onclick="displayDatePicker('expiration')"></div>
            <input type="hidden" id="expiration_date_value" value=""/>
            <div id="expiration_date_editor" class="property_flyout datepicker_wrapper">
                <div id="expiration_datepicker" class="task_datepicker"> </div>
            </div>
        </span>

        <span class="button disabled" id="add_message_button" style="margin-left:30px;vertical-align:top;margin-top:10px;">Post Message</span>
        <span class="button disabled" id="test_message_button" style="margin-left:30px;vertical-align:top;margin-top:10px;">Test Message</span>
    </div>

</div>




<div class="breath-20"></div>
<div class="breath-20"></div>
<h2>Posted Messages</h2>

<div style="text-decoration:underline;cursor:pointer;" onclick="loadMessagesOfType(0,'System Alerts')">Show Active System Alerts</div>
<div style="text-decoration:underline;cursor:pointer;" onclick="loadMessagesOfType(1,'Upgrade Based Messages')">Show Active Upgrade Based Messages</div>
<div style="text-decoration:underline;cursor:pointer;" onclick="loadMessagesOfType(2,'Account Duration Based Messages')">Show Active Account Duration Based Messages</div>
<div style="text-decoration:underline;cursor:pointer;" onclick="loadAllMessages()">Show All Messages (Active & Inactive)</div>



<div class="breath-20"></div>



<div class="admin_messages_container" id="messages_wrapper" style="display:none;">
    <h3 id="messages_title"></h3>

    <div id="message_date_wrapper" style="display:none;margin-bottom:10px;">
        <span style="margin-left:100px;">Show from:</span>
        <span id="start_date_wrap" style="display:inline-block;margin-left:10px;margin-right:10px;margin-top:4px;" class="property_wrapper">
            <div id="start_date_label" style="text-decoration:underline;cursor: pointer;" class="label" onclick="displayDatePicker('start')"></div>
            <input type="hidden" id="start_date_value" value=""/>
            <div id="start_date_editor" class="property_flyout datepicker_wrapper">
                <div id="start_datepicker" class="task_datepicker"> </div>
            </div>
        </span>

        <span>to</span>

        <span id="end_date_wrap" style="display:inline-block;margin-left:10px;margin-top:4px;" class="property_wrapper">
            <div id="end_date_label" style="text-decoration:underline;cursor: pointer;" class="label" onclick="displayDatePicker('end')"></div>
            <input type="hidden" id="end_date_value" value=""/>
            <div id="end_date_editor" class="property_flyout datepicker_wrapper">
                <div id="end_datepicker" class="task_datepicker"> </div>
            </div>
        </span>

        <span class="button" id="reload_messages_button" style="margin-left:10px;" onclick="">Go</span>
    </div>

    <div class="setting header">
        <div class="message_name">Subject</div>
        <div class="message_body">Message</div>
        <div class="message_date">Posted</div>
        <div class="message_date">Expires</div>
        <div class="message_name">Type</div>
        <div class="message_name">Priority</div>
        <div class="message_name">Devices</div>
        <div class="message_name">Sync Services</div>
        <div class="message_delete"></div>
    </div>
    <div id="messages_container" class="admin_messages_container"></div>
</div>

<div id="messages_loading_ui" style="display:none;"><span class="progress_indicator" style="display:inline-block"></span> Loading</div>



<div class="breath-20"></div>
<div class="breath-20"></div>
<h2>Tables</h2>

<div class="button" id="create_tables_button" onclick="createAllTables()">Create All Tables</div>
<div class="button" id="delete_tables_button" onclick="deleteAllTables()">Delete All Tables</div>

<div class="breath-20"></div>
<div id="message_center_tables"></div>
<div id="table_loading_ui" style="display:none;"><span class="progress_indicator" style="display:inline-block"></span> Loading</div>

<div class="breath-20"></div>

<script type="text/javascript" src="<?php echo TP_JS_PATH_ADMIN_MESSAGE_CENTER_FUNCTIONS; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_NEW_DATE_PICKER; ?>"></script>
<script type="text/javascript" src="<?php echo TP_JS_PATH_DATE_PICKER; ?>" ></script>

<script type="text/javascript">setUpMessageCenterPage()</script>
<?php
include_once('TodoOnline/base_sdk.php');
include_once('TodoOnline/DBConstants.php');

class TDOTeamSlackIntegration
{
    /**
     * Get all Slack channels by team id
     * @param string $teamID
     * @return array[]|bool
     */
    public static function getTeamSlackIntegrations($teamID)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::getTeamSlackIntegrations failed to get dblink");
            return false;
        }
        $result = array();
        $teamID = mysql_real_escape_string(trim($teamID));
        $sql = 'SELECT listid, webhook_url, channel_name, out_token FROM tdo_team_integration_slack WHERE teamid = "' . $teamID . '"';
        $mysql_result = mysql_query($sql, $link);
        if ($mysql_result) {
            while ($row = mysql_fetch_assoc($mysql_result)) {
                $result[$row['listid']] = array(
                    'webhook_url' => $row['webhook_url'],
                    'channel_name' => $row['channel_name'],
                    'out_token' => $row['out_token'],
                );
            }
            TDOUtil::closeDBLink($link);
            return $result;
        } else {
            error_log("Unable to get channels for team: " . mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    public static function setWebhookURLForChannelName($teamID, $listID, $channelName, $webhookURL, $outToken)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::setWebhookURLForChannelName failed to get dblink");
            return false;
        }
        $teamID = mysql_real_escape_string(trim($teamID));
        $listID = mysql_real_escape_string(trim($listID));
        $outToken = mysql_real_escape_string(trim($outToken));
        $channelName = mysql_real_escape_string(trim($channelName));
        $webhookURL = mysql_real_escape_string(trim($webhookURL));

        /**
         * Create or Update channel url.
         * Try to remove channel from DB if we get empty url string.
         *
         */
        if ($webhookURL !== '' || $outToken !== '') {
            if (self::getTeamSlackIntegrationForList($listID)) {
                /**
                 * Channel url already store default channel name.
                 * Notification will be sent to default channel if $channelName is empty.
                 */
                $sql = 'UPDATE tdo_team_integration_slack SET channel_name = "' . $channelName . '", webhook_url = "' . $webhookURL . '", out_token = "' . $outToken . '" WHERE teamid = "' . $teamID . '" AND listid = "' . $listID . '"';
                mysql_query($sql, $link);
            } else {
                $sql = 'INSERT INTO tdo_team_integration_slack VALUES("' . $teamID . '","' . $listID . '","' . $webhookURL . '","' . $channelName . '","' . $outToken . '")';
                mysql_query($sql, $link);
            }

        } else {
            TDOUtil::closeDBLink($link);
            return self::removeWebhookURLForChannelName($teamID, $listID);
        }

        TDOUtil::closeDBLink($link);
        return;
    }

    /**
     * Remove Slack integration for list
     *
     * @param $teamID
     * @param $listID
     * @return int|bool
     */
    public static function removeWebhookURLForChannelName($teamID, $listID)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::removeWebhookURLForChannelName failed to get dblink");
            return false;
        }
        $teamID = mysql_real_escape_string(trim($teamID));
        $listID = mysql_real_escape_string(trim($listID));
        $sql = 'DELETE FROM tdo_team_integration_slack WHERE teamid = "' . $teamID . '" AND listid = "' . $listID . '"';
        mysql_query($sql, $link);

        TDOUtil::closeDBLink($link);
        return mysql_affected_rows();
    }

    /**
     * Get webhook url and channel for list
     *
     * @param string $listID
     * @return array[]|bool
     */
    public static function getTeamSlackIntegrationForList($listID)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::getTeamSlackIntegrationForList failed to get dblink");
            return false;
        }
        $result = array();
        $listID = mysql_real_escape_string(trim($listID));
        $sql = 'SELECT webhook_url, channel_name, out_token FROM tdo_team_integration_slack WHERE listid = "' . $listID . '" LIMIT 1';
        $mysql_result = mysql_query($sql, $link);
        if ($mysql_result) {
            while ($row = mysql_fetch_assoc($mysql_result)) {
                $result = $row;
            }
            TDOUtil::closeDBLink($link);
            return $result;
        } else {
            error_log("Unable to get channels for team: " . mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    public static function processNotification($data, $action = 'save')
    {
        if (!isset($data['changeid']) || !$data['changeid']) {
            $data['changeid'] = TDOUtil::uuid();
        }
        $listid = $data['listid'];
        $list = TDOList::getListForListid($listid);
        if ($list) {
            $team = TDOTeamAccount::getTeamForTeamID($list->creator());
            if ($team) {
                $systemSettingTeamSlackIntegrationForInternalUseOnly = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY', SYSTEM_SETTING_SLACK_ENABLED_FOR_INTERNAL_USE_ONLY);
                $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId = TDOUtil::getStringSystemSetting('SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID', SYSTEM_SETTING_SLACK_ENABLED_FOR_TEAM_TEAM_ID);
                $systemSettingTeamSlackIntegrationForInternalUseOnly = $systemSettingTeamSlackIntegrationForInternalUseOnly == 'true'? true: false;
                if ($systemSettingTeamSlackIntegrationForInternalUseOnly == false || $team->getTeamID() === $systemSettingTeamSlackIntegrationForInternalUseOnlyTeamId) {
                    $team_status = TDOTeamAccount::getTeamSubscriptionStatus($team->getTeamID());
                    if ($team_status !== TEAM_SUBSCRIPTION_STATE_EXPIRED) {

                        $webhook = self::getTeamSlackIntegrationForList($listid);
                        if ($webhook) {
                            if (!$webhook) {
                                error_log("TDOTeamSlackIntegration::sendNotification failed get Slack config");
                                return false;
                            }
                            $webhook_url = $webhook['webhook_url'];
                            if ($webhook['channel_name']) {
                                $payload['channel'] = $webhook['channel_name'];
                            }


                            $payload = array(
                                'username' => 'Todo Cloud',
                                'icon_url' => 'https://www.todo-cloud.com/images/Todo-Cloud-Logo-100.png'
                            );

                            $message = self::getMessage($data);
                            if (!$message || $message === '') {
                                error_log("TDOTeamSlackIntegration::sendNotification message is empty");
                                return false;
                            }
                            $payload['text'] = $message;


                            $payload = json_encode($payload);
                            if ($action === 'save') {
                                self::saveNotification($data['changeid'], $webhook_url, $payload);
                            } elseif ($action === 'send') {
                                self::sendNotification($webhook_url, $payload);
                            } else {
                                self::saveNotification($data['changeid'], $webhook_url, $payload);
                            }
                        }
                    }
                }
            }
        }
    }

    private function sendNotification($webhook_url, $payload)
    {
        $curlHandle = curl_init();
        if (!$curlHandle) {
            error_log("TDOTeamSlackIntegration::sendNotification failed to init curl");
            return false;
        }

        curl_setopt($curlHandle, CURLOPT_URL, $webhook_url);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $payload);

        $result = curl_exec($curlHandle);
        if ($result === false) {
            error_log("TDOTeamSlackIntegration::sendNotification failed to send Slack notification");
        }
        curl_close($curlHandle);

        return true;
    }

    private function saveNotification($changeid, $webhook_url, $payload)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::saveNotification failed to get dblink");
            return false;
        }
        $payload = mysql_real_escape_string($payload);
        $sql = 'INSERT INTO tdo_slack_notifications VALUES("' . $changeid . '", NULL, "' . $webhook_url . '", "' . $payload . '", "' . time() . '")';
        return mysql_query($sql, $link);
    }

    /**
     * Create Notification message for Slack
     * @param array[] $data
     *
     * @return string $message
     */
    private function getMessage($data)
    {
        extract($data); // extract array items to variables
        /**
         * @var string $listid
         * @var string $userid
         * @var string $itemid
         * @var string $itemName
         * @var string $itemType
         * @var string $changeType
         * @var string $changeid
         * @var string $targetid
         */
        $userName = TDOUser::displayNameForUserId($userid);
        $userEmail = TDOUser::usernameForUserId($userid);
        $listName = TDOList::getNameForList($listid);

        $message = '';

        $message .= '_<mailto:' . $userEmail . '|' . $userName . '>_ ';
        switch ($itemType) {
            case ITEM_TYPE_COMMENT: {
                $comment = TDOComment::getCommentForCommentId($itemid);
                switch ($changeType) {
                    case CHANGE_TYPE_ADD:
                        if ($targetid == $listid) {
                            $message .= _('added a comment to') . '  *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>* ' . _('list');
                        } else {
                            $message .= _('added a comment to') . '  *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $itemName . '>* ';
                            $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        }
                        $message .= "\n" . '>>>' . $comment->text();
                        break;
                    case CHANGE_TYPE_DELETE:
                        if ($targetid == $listid) {
                            $message .= _('deleted a comment from') . '  *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>* ' . _('list');
                        } else {
                            $message .= _('deleted a comment from') . '  *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $itemName . '>* ';
                            $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        }
                        $message .= "\n" . '>>>' . $comment->text();
                        break;
                }
                break;
            }
            case ITEM_TYPE_TASKITO:
            case ITEM_TYPE_TASK: {
                if ($itemType == ITEM_TYPE_TASK) {
                    $taskName = htmlspecialchars(TDOTask::getNameForTask($itemid));
                } else {
                    $taskName = htmlspecialchars(TDOTaskito::getNameForTaskito($itemid));
                }
                switch ($changeType) {
                    case CHANGE_TYPE_ADD:
                        $message .= _('added') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                        $message .= _('to') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        break;
                    case CHANGE_TYPE_DELETE:
                        $message .= _('deleted') . ' *' . $taskName . '* ';
                        $message .= _('from') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        break;
                    case CHANGE_TYPE_MODIFY: {
                        $add_list_name = TRUE;
                        $changeData = TDOChangeLog::getChangeDataForChange($changeid);
                        if ($changeData == NULL) {
                            $message .= _('changed') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                            $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        } else {
                            $changes = json_decode($changeData, TRUE);
                            if (isset($changes['completiondate'])) {
                                if ($changes['completiondate'] == "0") {
                                    $message .= _('un-completed') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                } else {
                                    $message .= _('completed') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                }
                            } elseif (isset($changes['taskName'])) {
                                $message .= _('renamed') . ' _' . $changes['old-taskName'] . '_ ' . _('to') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                            } elseif (isset($changes['taskNote'])) {
                                if ($changes['old-taskNote'] === '') {
                                    $add_list_name = FALSE;
                                    $message .= _('added a note to') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                    $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                                    $message .= "\n" . '>>>' . $changes['taskNote'];
                                } elseif ($changes['taskNote'] === '') {
                                    $message .= _('removed a note from') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                } else {
                                    $add_list_name = FALSE;
                                    $message .= _('updated note for') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                    $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                                    $message .= "\n" . '>>>' . $changes['taskNote'];
                                }
                            } elseif (isset($changes['taskDueDate'])) {
                                if ($changes['taskDueDate'] > 0) {
                                    $message .= _('set') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* '._('due date to') . ' _' . TDOUtil::shortDueDateStringFromTimestamp($changes['taskDueDate']) . '_ ';
                                } else {
                                    $message .= _('removed due date for') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                }
                            } elseif (isset($changes['assignedUserId']) || (isset($changes['old-assignedUserId']) && !isset($changes['assignedUserId']))) {
                                if (!$changes['assignedUserId'] || $changes['assignedUserId'] == '') {
                                    $oldUserName = TDOChangeLog::getDisplayUserName($changes['old-assignedUserId']);
                                    $message .= _('unassigned') . ' _' . $oldUserName . '_ ' . _('from') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                } else {
                                    $assignedName = TDOChangeLog::getDisplayUserName($changes['assignedUserId']);
                                    $message .= _('assigned') . '_' . $assignedName . '_  ' . _('to') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';
                                }
                            } else {
                                $message .= _('updated') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showtask=' . $itemid . '|' . $taskName . '>* ';

                            }
                            if ($add_list_name) {
                                $message .= _('in') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                            }
                        }
                        break;
                    }
                }
                break;
            }
            case ITEM_TYPE_USER: {
                $userName = TDOUser::displayNameForUserId($itemid);
                $userEmail = TDOUser::usernameForUserId($itemid);
                $message = '_<mailto:' . $userEmail . '|' . $userName . '>_ ';
                switch ($changeType) {

                    case CHANGE_TYPE_ADD: {

                        $message .= _('joined the') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        break;
                    }
                    case CHANGE_TYPE_DELETE: {
                        if ($userid == $itemid) {
                            $message .= _('left the') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        } else {
                            $message .= _('removed from') . ' *<' . SITE_PROTOCOL . SITE_BASE_URL . '/?showlist=' . $listid . '|' . $listName . '>*';
                        }
                        break;
                    }
                }
                break;
            }
            default: {
                break;
            }


        }
        return $message;

    }

    private function getListidByToken($token)
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOTeamSlackIntegration::getListByToken failed to get dblink");
            return false;
        }
        $result = array();
        $token = mysql_real_escape_string(trim($token));
        $sql = 'SELECT listid FROM tdo_team_integration_slack WHERE out_token = "' . $token . '" LIMIT 1';
        $mysql_result = mysql_query($sql, $link);
        if ($mysql_result) {
            $row = mysql_fetch_assoc($mysql_result);
            $result = $row['listid'];
            TDOUtil::closeDBLink($link);
            return $result;
        } else {
            error_log("Unable to get lists for token: " . mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    public static function createTaskFromSlack()
    {
        $log_message = array();
        $token = $_POST['token'];
        if ($token) {
            $log_message[] = 'Token: ' . $token;
            $trigger_word = $_POST['trigger_word'];
            $text = $_POST['text'];
            $log_message[] = 'Message Text: ' . $text;
            preg_match('/^(' . $trigger_word . ')(.+)/i', $text, $taskName);

            if (isset($taskName) && is_array($taskName) && sizeof($taskName)) {
                $taskName = trim($taskName[2]);
            }
            if ($taskName) {
                $log_message[] = 'Task Name: ' . $taskName;
                $listid = self::getListidByToken($token);
                if ($listid) {
                    $log_message[] = 'List ID: ' . $listid;
                    $teamid = TDOList::teamIDForList($listid);
                    $log_message[] = 'Team ID: ' . $teamid;
                    $teamAccount = TDOTeamAccount::getTeamForTeamID($teamid);
                    $adminid = $teamAccount->getBillingUserID();
                    if (!$adminid) {
                        $admins = TDOTeamAccount::getAdminUserIDsForTeam($teamid);
                        if ($admins && sizeof($admins)) {
                            $adminid = $admins[0];
                        }
                    }
                    $log_message[] = 'Admin ID: ' . $adminid;
                    $newTask = new TDOTask();

                    $newTask->setListid($listid);
                    $newTask->setName($taskName);
                    if ($newTask->addObject()) {
                        $log_message[] = 'Task Added: ' . $newTask->name() . '(' . $newTask->taskId() . ')';
                        TDOChangeLog::addChangeLog($listid, $adminid, $newTask->taskId(), $newTask->name(), ITEM_TYPE_TASK, CHANGE_TYPE_ADD, CHANGE_LOCATION_WEB);
                    } else {
                        $log_message[] = 'Failed to add task';
                        error_log("TDOTeamSlackIntegration::createTaskFromSlack failed to add task: " . $newTask->taskId());
                    }
                } else {
                    $log_message[] = 'Failed to get listid';
                }
            } else {
                $log_message[] = 'Task name is empty';
            }
        } else {
            $log_message[] = 'Token missing';
        }

        foreach ($log_message as $k => $v) {
            $log_message[$k] = '`' . $v . '`';
        }

        $payload = array(
            'text' => implode("\n", $log_message),
            'username' => 'Todo Cloud',
            'icon_url' => 'https://www.todo-cloud.com/images/Todo-Cloud-Logo-100.png'
        );
        echo json_encode($payload);
    }
}
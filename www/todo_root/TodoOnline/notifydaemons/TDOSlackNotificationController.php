<?php

include_once('TDODaemonConfig.php');
include_once('TodoOnline/base_sdk.php');
//include_once('Plunkboard/DBConstants.php');
include_once('TDODaemonLogger.php');
include_once('TDODaemonController.php');

class TDOSlackNotificationController extends TDODaemonController
{

    function __construct($daemonID = '')
    {
        parent::__construct($daemonID);
    }

    public function queueNotifications()
    {
        $notification_count = $this->markRecords();
        if ($notification_count) {
            $affected_notifications_ids = $this->processRecords();
            if ($affected_notifications_ids && sizeof($affected_notifications_ids)) {
                return $this->deleteRecords($affected_notifications_ids);
            }
        }
        return FALSE;

    }


    private function markRecords()
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOSlackNotificationController::markRecords failed to get dblink");
            return false;
        }

        $sql = 'UPDATE tdo_slack_notifications SET queue_daemon_owner="' . $this->guid . '" WHERE (queue_daemon_owner="" OR queue_daemon_owner IS NULL)';
        $mysql_result = mysql_query($sql, $link);
        if (!$mysql_result) {
            error_log("TDOSlackNotificationController->markRecords unable to get slack notifications: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return 0;
        }

        $markedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        return $markedRowCount;
    }

    private function processRecords()
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOSlackNotificationController::getTeamSlackIntegrations failed to get dblink");
            return false;
        }
        $selected_ids = array();
        $sql = 'SELECT * FROM tdo_slack_notifications WHERE queue_daemon_owner="' . $this->guid . '" ORDER BY timestamp';
        $mysql_result = mysql_query($sql, $link);
        if ($mysql_result) {
            if (mysql_num_rows($mysql_result)) {
                while ($row = mysql_fetch_assoc($mysql_result)) {
                    $selected_ids[] = $row['changeid'];
                    $this->sendNotification($row);
                }
            }
            TDOUtil::closeDBLink($link);

            return $selected_ids;
        } else {
            error_log("TDOSlackNotificationController->queueNotifications unable to get slack notifications: " . mysql_error());
        }

        TDOUtil::closeDBLink($link);
        return false;
    }

    private function deleteRecords()
    {
        $link = TDOUtil::getDBLink();
        if (!$link) {
            error_log("TDOSlackNotificationController::markRecords failed to get dblink");
            return false;
        }

        $daemon_owner = mysql_real_escape_string($this->guid, $link);
        $sql = 'DELETE FROM tdo_slack_notifications WHERE queue_daemon_owner = "' . $this->guid . '"';

        $mysql_result = mysql_query($sql, $link);
        if (!$mysql_result) {
            error_log("TDOSlackNotificationController->deleteRecords unable delete notifications: " . mysql_error());
            TDOUtil::closeDBLink($link);
            return false;
        }

        $deletedRowCount = mysql_affected_rows($link);
        TDOUtil::closeDBLink($link);
        return $deletedRowCount;
    }

    private function sendNotification($data)
    {
        $curlHandle = curl_init();
        if (!$curlHandle) {
            error_log("TDOSlackNotificationController::sendNotification failed to init curl");
            return false;
        }

        curl_setopt($curlHandle, CURLOPT_URL, $data['webhook_url']);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data['payload']);

        $result = curl_exec($curlHandle);
        if ($result === false) {
            error_log("TDOSlackNotificationController::sendNotification failed to send Slack notification");
        }
        curl_close($curlHandle);

        return true;
    }

}


?>


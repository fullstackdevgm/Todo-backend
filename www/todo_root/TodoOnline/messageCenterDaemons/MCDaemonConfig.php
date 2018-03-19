<?php

define('LOG_PATH', '/var/log/messageCenterDaemon/');
define('LOG_SIZE_LIMIT', 20971520); // 20Mb
define('DAEMON_UID', 1000);
define ('DAEMON_GID', 1000);

// Sleep for 24 hours between checking for expired messages
define('EXPIRATION_AUTORENEW_SLEEP_INTERVAL', 60 * 60 * 24);
// Sleep for 1 hour between checking for expired messages if there was a problem
define('EXPIRATION_AUTORENEW_SLEEP_INTERVAL_ERROR', 60 * 60);

?>

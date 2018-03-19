<?php

define('LOG_PATH', '/var/log/todo-cloud/');
define('LOG_SIZE_LIMIT', 20971520); // 20Mb
define('DAEMON_UID', 1000);
define ('DAEMON_GID', 1000);

// Sleep for 1 hour between attempts to process autorenewals
//define('SUBSCRIPTION_AUTORENEW_SLEEP_INTERVAL', 3600); // Moved to a system setting (statics.php)

?>

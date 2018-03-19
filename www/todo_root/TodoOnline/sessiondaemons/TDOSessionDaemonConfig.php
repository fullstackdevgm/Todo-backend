<?php

//include_once('QueueNotifications.php');

define('LOG_PATH', '/var/log/todo-cloud/');
define('LOG_SIZE_LIMIT', 20971520); // 20Mb
define('DAEMON_UID', 1000);
define ('DAEMON_GID', 1000);

// We don't need to run this too often because it's just used to clean up expired sessions
define('QUEUE_PROCESS_SLEEP', 86400);



?>

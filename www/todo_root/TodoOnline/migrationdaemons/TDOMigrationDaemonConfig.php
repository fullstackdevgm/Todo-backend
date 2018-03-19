<?php

//include_once('QueueNotifications.php');

define('LOG_PATH', '/var/log/tdoMigrationDaemon/');
define('LOG_SIZE_LIMIT', 20971520); // 20Mb
define('DAEMON_UID', 1000);
define ('DAEMON_GID', 1000);

//
// Queue Daemon
//
// (Boyd) Tested loop limit @ 10 - Took a minute and a half to process through
// 2000 tasks all scheduled at the same time.  It was held up in the
// queue daemons.
define('QUEUE_PROCESS_LOOP_LIMIT', 1000);
define('QUEUE_PROCESS_FAST_SLEEP', 0.001);
// (Boyd) Tested 15 for slow sleep.  Daemons "kick in" late so I'm dropping this
// down to 10 to see if that improves anything.
define('QUEUE_PROCESS_SLOW_SLEEP', 5);
// When processing alerts, only send them to the user if they
// fall within a valid window.  This should prevent the server
// from ever sending a notification outside of a normal time
// frame.  One scenario where this could happen is if a user has
// a bunch of notifications, sets their text_alerts=0 (turn off
// notifications) and then later re-enables them.  We don't
// want to flood them with alerts between "now" and their new
// alert interval time.  This value also helps us keep up if the
// server gets really busy.  To be more lenient, specify 0.1.  To
// get very restrictive, specify something like 0.9.
// (Boyd) Tested - 0.8 (60 seconds of 5 minutes).  We dropped many tasks
// when the server got really busy.
define('QUEUE_PROCESS_VALID_WINDOW_FACTOR', 0.5);



//
// Push Daemon
//
define('PUSH_PROCESS_FAST_SLEEP', 0.001);
define('PUSH_PROCESS_SLOW_SLEEP', 15);


?>

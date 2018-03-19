pids=`ps -ef | grep queueDaemonA | wc -l`;
if [ $pids -eq 1 ]; then
	/etc/init.d/queueDaemonA start
fi

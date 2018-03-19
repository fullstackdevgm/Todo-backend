pids=`ps -ef | grep mcexpirationd | wc -l`;
if [ $pids -eq 1 ]; then
	/etc/init.d/mcexpirationd start
fi

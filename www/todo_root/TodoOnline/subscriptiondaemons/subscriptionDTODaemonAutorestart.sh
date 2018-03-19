pids=`ps -ef | grep tdosubscriptiond | wc -l`;
if [ $pids -eq 1 ]; then
	/etc/init.d/tdosubscriptiond start
fi

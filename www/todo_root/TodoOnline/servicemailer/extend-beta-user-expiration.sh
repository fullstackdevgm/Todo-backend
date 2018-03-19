#!/bin/bash

#PROCESSED_COUNT=0

cat $1 |cut -d, -f2 |sort --ignore-case |while read line
do
	EMAIL=`echo $line`
	echo "$EMAIL"

	php extend-beta-user-expiration.php "$EMAIL"

done



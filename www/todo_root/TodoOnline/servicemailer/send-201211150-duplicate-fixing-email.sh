#!/bin/bash

PROCESSED_COUNT=0

cat $1 | while read line
do
	FIRST_NAME=`echo $line |cut -d, -f2`
	EMAIL=`echo $line |cut -d, -f1`
	echo "$EMAIL, $FIRST_NAME"

	php 20121115-Duplicate-Fixing.php "$EMAIL" "$FIRST_NAME"

	PROCESSED_COUNT=$(($PROCESSED_COUNT + 1))
	REMAINDER=$((PROCESSED_COUNT % 50))
	if [ "$REMAINDER" = "0" ]
	then
		echo "DEBUG: SLEEPING FOR 1 SECOND"
		sleep 1;
	fi
done


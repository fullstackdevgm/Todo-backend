#!/bin/bash

PROCESSED_COUNT=0

cat $1 | while read line
do
	FIRST_NAME=`echo $line |cut -d, -f2`
	LAST_NAME=`echo $line |cut -d, -f3`
	EMAIL=`echo $line |cut -d, -f1`
	OPTED_OUT=`echo $line |cut -d, -f4`
	#echo "First Name = '$FIRST_NAME', Last Name = '$LAST_NAME', Email = '$EMAIL', Opted Out = '$OPTED_OUT'"
	echo "$EMAIL, $FIRST_NAME"

	php todo-online-service-email.php "$EMAIL" "$FIRST_NAME"
	#php test.php "$EMAIL" "$FIRST_NAME"

	PROCESSED_COUNT=$(($PROCESSED_COUNT + 1))
	REMAINDER=$((PROCESSED_COUNT % 50))
	if [ "$REMAINDER" = "0" ]
	then
		echo "DEBUG: SLEEPING FOR 1 SECOND"
		sleep 1;
	fi
done



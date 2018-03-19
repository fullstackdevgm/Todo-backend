#/bin/bash

SCRIPT_BASE_DIR=`pwd`/`dirname $0`

while true;
do

if [ -z "$1" ];
then
    /usr/bin/php $SCRIPT_BASE_DIR/migrateUser.php processQueue=true
else
    /usr/bin/php $SCRIPT_BASE_DIR/migrateUser.php id=$1 processQueue=true
fi


done


#/bin/bash

SCRIPT_BASE_DIR=`pwd`/`dirname $0`

while true;
do

if [ -z "$1" ];
then
    /usr/bin/php $SCRIPT_BASE_DIR/repairTaskDupes.php rundaemon=true dupeCount=2
else
    /usr/bin/php $SCRIPT_BASE_DIR/repairTaskDupes.php rundaemon=true dupeCount=2 id=$1
fi


done


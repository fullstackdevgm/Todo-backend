#!/bin/bash

set -e

IDENTITY="-i /Users/boyd/.ssh/appigokey.pem"

SCRIPT_BASE_DIR=`pwd`/`dirname $0`

cd $SCRIPT_BASE_DIR

if [ "$2" == "production" ];
then
    read -p "You are about to change the production machines.  Is that right? (YES/n) " RESP
    if [ "$RESP" = "YES" ]; then
        echo "OK THEN, working with production!"
    else
        echo "Aren't you glad now that I asked?"
    exit 1;
    fi

    source $SCRIPT_BASE_DIR/config-production.sh
elif [ "$2" == "zeta" ];
then
    source $SCRIPT_BASE_DIR/config-zeta.sh
else
    source $SCRIPT_BASE_DIR/config-local.sh
fi


if [ "$1" == "" ];
then
    echo ""
    echo "Usage: todoproctl.sh < up | down > [ production | zeta ]"
    echo ""
    exit 0;
fi


# commands to take the servers down
if [ "$1" == "down" ];
then

    for aServer in "${rolloutservers[@]}"
    do
    :
        # do whatever on $aServer
        echo "Taking down amazon server: $aServer"

        # unlink the current Todo Pro config
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/002-todopro.com'        
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/004-siri.todopro.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/006-sync.todopro.com'

        # unlink the todo CLOUD configs
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/001-todo-cloud.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/003-siri.todo-cloud.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/005-sync.todo-cloud.com'

        # link the downed configs
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/down.todopro.com /etc/apache2/sites-enabled/002-down.todopro.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/down.sync.todopro.com /etc/apache2/sites-enabled/006-down.sync.todopro.com'

        # restart apached on the server
        ssh $IDENTITY ubuntu@$aServer 'sudo /etc/init.d/apache2 restart'

    done
fi



# commands to bring the server up
if [ "$1" == "up" ];
then

    for aServer in "${rolloutservers[@]}"
    do
    :
        # do whatever on $aServer
        echo "Bringing up the amazon server: $aServer"

        # unlink the downed configs
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/002-down.todopro.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo rm -f /etc/apache2/sites-enabled/006-down.sync.todopro.com'

        # link the Todo Pro configs
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/todopro.com /etc/apache2/sites-enabled/002-todopro.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/siri.todopro.com /etc/apache2/sites-enabled/004-siri.todopro.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/sync.todopro.com /etc/apache2/sites-enabled/006-sync.todopro.com'

        # link the Todo Cloud config
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/todo-cloud.com /etc/apache2/sites-enabled/001-todo-cloud.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/siri.todo-cloud.com /etc/apache2/sites-enabled/003-siri.todo-cloud.com'
        ssh $IDENTITY ubuntu@$aServer 'sudo ln -s /etc/apache2/sites-available/sync.todo-cloud.com /etc/apache2/sites-enabled/005-sync.todo-cloud.com'

        # restart apached on the server
        ssh $IDENTITY ubuntu@$aServer 'sudo /etc/init.d/apache2 restart'

    done

fi

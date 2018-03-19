#!/bin/bash

SCRIPT_BASE_DIR=`pwd`/`dirname $0`

cd $SCRIPT_BASE_DIR

# Dynamically create the revision.php file without affecting SVN. Upload a newly
# created file to the server so it can be used in the About Screen (Bug #6929).
SVN_REVISION=`/usr/bin/svnversion $SCRIPT_BASE_DIR`
VERSION_FILE=/tmp/version.php
ROLLOUT_USER=`id -un`
DEV_SVN_REVISION="$SVN_REVISION.$ROLLOUT_USER"
cp "$SCRIPT_BASE_DIR/TodoOnline/version.php" "/tmp/version.php"
#echo "<?php" > $VERSION_FILE
#echo "define('TDO_VERSION', '2.0');" >> $VERSION_FILE

# Determine if we are rolling out to production or development
# Valid command-line arguments are:
#
#	1. <none>		- This will roll out to local servers
#	2. "production"	- This will roll out to amazon servers


if [ "$1" == "production" ];
then
    read -p "You are about to rollout to production.  Is that right? (YES/n) " RESP
    if [ "$RESP" = "YES" ]; then
        echo "OK THEN, rolling out to production!"
    else
        echo "Aren't you glad now that I asked?"
        exit 1;
    fi

    source $SCRIPT_BASE_DIR/config-production.sh
    sed -i "" "s/define('TDO_REVISION', 'LOCAL');/define('TDO_REVISION', '$SVN_REVISION');/g" "/tmp/version.php"
    #echo "define('TDO_REVISION', '$SVN_REVISION');" >> $VERSION_FILE

elif [ "$1" == "auth" ];
then

    source $SCRIPT_BASE_DIR/config-auth.sh
    # Record who last rolled out to the development server
    sed -i "" "s/define('TDO_REVISION', 'LOCAL');/define('TDO_REVISION', '$DEV_SVN_REVISION');/g" "/tmp/version.php"
    #echo "define('TDO_REVISION', '$DEV_SVN_REVISION');" >> $VERSION_FILE

elif [ "$1" == "stage" ];
then

    source $SCRIPT_BASE_DIR/config-stages.sh
    # Record who last rolled out to the development server
    sed -i "" "s/define('TDO_REVISION', 'LOCAL');/define('TDO_REVISION', '$DEV_SVN_REVISION');/g" "/tmp/version.php"
    #echo "define('TDO_REVISION', '$DEV_SVN_REVISION');" >> $VERSION_FILE

# default rollout is to plano
else

    source $SCRIPT_BASE_DIR/config-plano.sh
    # Record who last rolled out to the development server
    sed -i "" "s/define('TDO_REVISION', 'LOCAL');/define('TDO_REVISION', '$DEV_SVN_REVISION');/g" "/tmp/version.php"
    #echo "define('TDO_REVISION', '$DEV_SVN_REVISION');" >> $VERSION_FILE

fi


for ROLLOUT_SERVER in "${rolloutservers[@]}"
do
:

echo "Rolling out to $ROLLOUT_SERVER"

#ssh ubuntu@$ROLLOUT_SERVER 'rm -rf ~/TodoOnline'
#ssh ubuntu@$ROLLOUT_SERVER 'mkdir TodoOnline'
#scp -r TodoOnline ubuntu@$ROLLOUT_SERVER:TodoOnline
#scp -r docroot ubuntu@$ROLLOUT_SERVER:TodoOnline

# Use RSYNC to transfer the files to the server
rsync -zvrL --exclude '*svn-base' --exclude '*\.svn/*' --exclude '*\.DS_Store' --exclude '*\.svn' --delete --delete-excluded TodoOnline ubuntu@$ROLLOUT_SERVER:TodoOnline/
rsync -zvrL --exclude '*svn-base' --exclude '*\.svn/*' --exclude '*\.DS_Store' --exclude '*\.svn' --delete --delete-excluded docroot ubuntu@$ROLLOUT_SERVER:TodoOnline/

# copy the dynamic version.php file to the server
scp $VERSION_FILE ubuntu@$ROLLOUT_SERVER:TodoOnline/TodoOnline/

# scp -r caldav ubuntu@$ROLLOUT_SERVER:TodoOnline

# remove the directory and get a clean install of Plunkboard out there
#ssh ubuntu@$ROLLOUT_SERVER 'sudo rm -rf /usr/share/php/TodoOnline'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo mkdir /usr/share/php/TodoOnline'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/TodoOnline/* /usr/share/php/TodoOnline'

ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/TodoOnline /usr/share/php/'

# move the server config file into place
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/DBServerCredentials.php /usr/share/php/TodoOnline/DBCredentials.php'

if [ "$1" == "production" ];
then
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp ~/DBCredentials.php /usr/share/php/TodoOnline/DBCredentials.php'
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/config_prod.php /usr/share/php/TodoOnline/config.php'
elif [ "$1" == "auth" ];
then
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp ~/DBCredentials.php /usr/share/php/TodoOnline/DBCredentials.php'
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/config_prod.php /usr/share/php/TodoOnline/config.php'
elif [ "$1" == "stage" ];
then
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp ~/DBCredentials.php /usr/share/php/TodoOnline/DBCredentials.php'
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/config_stage.php /usr/share/php/TodoOnline/config.php'
else
ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/config_plano.php /usr/share/php/TodoOnline/config.php'
fi

# create a fresh install for facebook
#ssh ubuntu@$ROLLOUT_SERVER 'sudo rm -rf /var/www/todopro.com/*'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/todoonline/* /var/www/todopro.com'
ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/docroot/todopro.com /var/www/'

#ssh ubuntu@$ROLLOUT_SERVER 'sudo mkdir /var/www/todopro.com/admin'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/admin/* /var/www/todopro.com/admin'
ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/docroot/pigeon47 /var/www/todopro.com/'

#ssh ubuntu@$ROLLOUT_SERVER 'sudo mkdir /var/www/todopro.com/sync'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/sync/* /var/www/todopro.com/sync'
ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/docroot/sync /var/www/todopro.com/'

#ssh ubuntu@$ROLLOUT_SERVER 'sudo rm -rf /var/www/siri.todopro.com'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo mkdir /var/www/siri.todopro.com'
#ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/caldav/* /var/www/siri.todopro.com'
ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/docroot/siri.todopro.com /var/www/'


#auth service for support website
ssh ubuntu@$ROLLOUT_SERVER 'sudo rsync -zrL --delete ~/TodoOnline/docroot/auth.appigo.com /var/www/'



# remove the dbManager.php file from the server
ssh ubuntu@$ROLLOUT_SERVER 'sudo rm /var/www/todopro.com/pigeon47/manageDB.php';


# restart the notify and migration daemons
# CRG - this hangs the script for some reason
# ssh ubuntu@$ROLLOUT_SERVER 'sudo /etc/init.d/tdonotifyd restart'
# ssh ubuntu@$ROLLOUT_SERVER 'sudo /etc/init.d/tdomigrated restart'


# create a fresh install for caldav
# ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/caldav/index.php /var/www/cal.plunkboard'
# We no longer need the htaccess file because we do it in the apache config file
# ssh ubuntu@new.plunkboard.com 'sudo cp -r ~/TodoOnline/caldav/dot-htaccess /var/www/cal.TodoOnline/.htaccess'

# create a fresh install for admin
# ssh ubuntu@$ROLLOUT_SERVER 'sudo rm -rf /var/www/pigeon.TodoOnline/*'
# ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/admin/index.php /var/www/pigeon.plunkboard'
# ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/fb.TodoOnline/css /var/www/pigeon.plunkboard'
# ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/fb.TodoOnline/images /var/www/pigeon.plunkboard'

# if [ "$ROLLOUT_SERVER" == "dev.plunkboard.com" ];
# then
	# copy over the dev config files
# 	ssh ubuntu@$ROLLOUT_SERVER 'sudo cp /usr/share/php/TodoOnline/dev_config.php /usr/share/php/TodoOnline/config.php'

	# copy over the pigeonboard graphics to dev, fb, and pigeon
# 	ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/pigeonboard/images/* /var/www/todoonline/images'
	# ssh ubuntu@$ROLLOUT_SERVER 'sudo cp -r ~/TodoOnline/docroot/pigeonboard/images/* /var/www/pigeon.TodoOnline/images'
# fi

done


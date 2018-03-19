#!/bin/bash

#AWSCMD="perl ../../frameworks/aws-cmdline/aws"
AWSCMD="/usr/local/bin/aws"
CACHE_SECONDS=2592000

####### IMPORTANT ########
# You must create a ~/.awssecret file and include your AWS Access Key on the first
# line and your AWS Secret Key on the second line of the file.  If you do not do
# this, the script will not have any credentials for communicating with S3.

# Determine if we are rolling out to the production or development server
# Valid command-line arguments are:
#
#	1. <none>	- Ths will roll out to the development server
#	2. "production"	- This will roll out to the Amazon production system

# Temporarily move the local base.css out of the way so that the proper
# version gets pushed to S3.
TMP_DIR_NAME=`date |md5`
TMP_DIR=/tmp/$TMP_DIR_NAME

if [ -d "$TMP_DIR" ]
then
	rm -rf $TMP_DIR
fi
mkdir $TMP_DIR

mv www/todopro.com/css/*base.css $TMP_DIR/

if [ "$1" == "production" ]
then
    read -p "You are about to rollout to production.  Is that right? (YES/n) " RESP
    if [ "$RESP" = "YES" ]; then
        echo "OK THEN, rolling out to production!"
    else
        echo "Aren't you glad now that I asked?"
        exit 1;
    fi


	S3_BUCKET="2014.todo-cloud.com"
	cp $TMP_DIR/prod_base.css www/todopro.com/css/base.css
else
	S3_BUCKET="2014-dev.todo-cloud.com"
	cp $TMP_DIR/dev_base.css www/todopro.com/css/base.css
fi

echo "ROLLOUT SERVER: $S3_BUCKET"

##########################
# www/todopro.com/css/
##########################
pushd www/todopro.com/images

DEST_PATH=s3://$S3_BUCKET/images/
$AWSCMD s3 sync . $DEST_PATH --acl public-read --cache-control "max-age=$CACHE_SECONDS" --exclude "*" --include "*.png" --include "*.jpg" --include "*.gif"

popd

##########################
# www/todopro.com/css/
##########################
pushd www/todopro.com/css/
DEST_PATH=s3://$S3_BUCKET/css/
$AWSCMD s3 sync . $DEST_PATH --acl public-read --cache-control "max-age=$CACHE_SECONDS" --exclude "*" --include "*.css"
popd

##########################
# www/todopro.com/js/
##########################
pushd www/todopro.com/js/
DEST_PATH=s3://$S3_BUCKET/js/
$AWSCMD s3 sync . $DEST_PATH --acl public-read --cache-control "max-age=$CACHE_SECONDS" --exclude "*" --include "*.js"
popd

##########################
# www/todopro.com/html/
##########################
pushd www/todopro.com/html/
DEST_PATH=s3://$S3_BUCKET/html/
$AWSCMD s3 sync . $DEST_PATH --acl public-read --cache-control "max-age=$CACHE_SECONDS" --exclude "*" --include "*.html"
popd




##########################
# Admin JavaScript Files
# ##########################
# pushd www/pigeon47/js/
# DEST_PATH=s3://$S3_BUCKET/js/
# $AWSCMD s3 sync . $DEST_PATH --acl public-read --cache-control "max-age=$CACHE_SECONDS" --exclude "*" --include "*.js"
# popd

# Cleanup the TMP diretory
cp $TMP_DIR/*.css www/todopro.com/css/
if [ -d "$TMP_DIR" ]
then
	rm -rf $TMP_DIR
fi



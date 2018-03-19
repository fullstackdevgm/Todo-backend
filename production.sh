#!/bin/sh

GIT_COMMIT_HASH=`git rev-parse HEAD`
export GIT_REVISION=${GIT_COMMIT_HASH:0:10}

export AWS_ACCESS_KEY_ID=`cat ~/.aws/access_key_id`
export AWS_SECRET_ACCESS_KEY=`cat ~/.aws/secret_access_key`
export SHELL_ARGS=production_main

if [ -z "$1" ];
then
	echo "Syntax: production [up|provision|halt|destroy|ssh]"
	exit 1
else
	ACTION=$1
fi

case "$ACTION" in
	up)
		VAGRANT_VAGRANTFILE="Vagrantfile.main" vagrant up --provider=aws
		;;
	provision)
		VAGRANT_VAGRANTFILE="Vagrantfile.main" vagrant provision
		;;
	halt)
		VAGRANT_VAGRANTFILE="Vagrantfile.main" vagrant halt
		;;
	destroy)
		VAGRANT_VAGRANTFILE="Vagrantfile.main" vagrant destroy
		;;
	ssh)
		VAGRANT_VAGRANTFILE="Vagrantfile.main" vagrant ssh
		;;
	*)
		echo "Syntax: production [up|provision|halt|destroy|ssh]"
		exit 1
esac


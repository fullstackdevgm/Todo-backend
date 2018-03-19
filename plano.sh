#!/bin/sh

GIT_COMMIT_HASH=`git rev-parse HEAD`
export GIT_REVISION=${GIT_COMMIT_HASH:0:10}

export AWS_ACCESS_KEY_ID=`cat ~/.aws/access_key_id`
export AWS_SECRET_ACCESS_KEY=`cat ~/.aws/secret_access_key`
export SHELL_ARGS=plano

if [ -z "$1" ];
then
	echo "Syntax: plano [up|provision|halt|destroy|ssh|rsync]"
	exit 1
else
	ACTION=$1
fi

case "$ACTION" in
	up)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant up --provider=aws
		;;
	provision)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant provision
		;;
	halt)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant halt
		;;
	destroy)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant destroy
		;;
	ssh)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant ssh
		;;
	rsync)
		VAGRANT_VAGRANTFILE="Vagrantfile.plano" vagrant rsync
		;;
	*)
		echo "Syntax: plano [up|provision|halt|destroy|ssh|rsync]"
		exit 1
esac


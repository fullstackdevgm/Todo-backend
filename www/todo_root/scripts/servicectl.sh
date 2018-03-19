#!/bin/bash
SHELL=/bin/bash

TDO_SERVER_TYPE=`cat /vagrant/www/todo_root/TodoOnline/config.php |grep TDO_SERVER_TYPE |cut -d "'" -f 4`

if [ "$(whoami)" != "root" ];
then
	echo "Must be run as root!"
	exit 1
fi

if [ -z "$1" ];
then
	echo ""
	echo "Usage: servicectl.sh <up | down>"
	echo ""
	exit 0
fi

case "$TDO_SERVER_TYPE" in
	local)
		DOWN_LIST=('down.local');
		UP_LIST=('local');
		;;
	plano)
		DOWN_LIST=('down.plano.todo-cloud.com');
		UP_LIST=('plano.todo-cloud.com');
		;;
	production)
		DOWN_LIST=('down.sync.todo-cloud.com' 'down.sync.todopro.com' 'down.todo-cloud.com' 'down.todopro.com');
		UP_LIST=('default' 'siri.todo-cloud.com' 'siri.todopro.com' 'sync.todo-cloud.com' 'sync.todopro.com' 'todo-cloud.com' 'todopro.com');
		;;
	*)
		echo "Could not determine server type."
		exit 1
		;;
esac

case "$1" in
	up)
		sudo rm /etc/apache2/sites-enabled/*
		for i in ${UP_LIST[@]}; do
			sudo a2ensite ${i}
		done
		;;
	down)
		sudo rm /etc/apache2/sites-enabled/*
		for i in ${DOWN_LIST[@]}; do
			sudo a2ensite ${i}
		done
		;;
	*)
		echo "Usage: servicectl.sh <up | down>"
		exit 1
		;;
esac

service apache2 restart

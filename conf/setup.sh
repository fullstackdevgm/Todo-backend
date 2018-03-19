#
# This script pays attention to the SHELL_ARGS environment variable. Possible
# values are:
#
#     SHELL_ARGS='local|plano|beta|production_auth|production_main'
#
# This script is run by the "vagrant up" command.
#
# If no SHELL_ARGS is specified, the script will assume that this is a
# local deployment. The staging and production builds are designed for
# the Amazon AWS environment. Development builds are meant to run standalone
# on a developer's own computer.

# TODO: Need to copy in all the correct apache site files for auth and production
# instead of using the existing production.apache.conf file which looks like it's only a
# staging server.

if [ -z "$1" ];
then
	PROVISION_TYPE='local'
else
	PROVISION_TYPE=$1
fi

case "$PROVISION_TYPE" in
	local)
		SET_UP_DAEMONS="false"
		HOSTNAME="localhost"
		USE_MYSQL_5_6="true"
		;;
	plano)
		SET_UP_DAEMONS="true"
		HOSTNAME="plano.todo-cloud.com"
		USE_MYSQL_5_6="true"
		;;
	beta)
		SET_UP_DAEMONS="false"
		HOSTNAME="beta.todo-cloud.com"
		USE_MYSQL_5_6="false"
		;;
	production_auth)
		# The auth.appigo.com server
		SET_UP_DAEMONS="true"
		HOSTNAME="auth.appigo.com"
		USE_MYSQL_5_6="false"
		;;
	production_main)
		# The standard servers behind the load balancer
		SET_UP_DAEMONS="false"
		HOSTNAME="www.todo-cloud.com"
		USE_MYSQL_5_6="false"
		;;
	*)
		echo "UNKNOWN PROVISION TYPE. Must be one of: development, staging, production_auth, or production_web"
		exit 1
		;;
esac

echo "### PROVISION TYPE: $PROVISION_TYPE"

# Uncomment this section to install MySQL 5.6 Server
#if [ "$SET_UP_DAEMONS" == "true" ]
#then
#wget -q http://dev.mysql.com/get/mysql-apt-config_0.5.3-1_all.deb -O mysql-apt-config_0.5.3-1_all.deb
#echo mysql-apt-config	mysql-apt-config/select-workbench	select	none | debconf-set-selections
#echo mysql-apt-config mysql-apt-config/select-server select mysql-5.6 | debconf-set-selections
#echo mysql-apt-config	mysql-apt-config/select-router	select | debconf-set-selections
#echo mysql-apt-config	mysql-apt-config/select-connector-python	select	none | debconf-set-selections
#echo mysql-apt-config	mysql-apt-config/select-connector-odbc	select | debconf-set-selections
#echo mysql-apt-config mysql-apt-config/select-product select Apply | debconf-set-selections
#echo mysql-apt-config	mysql-apt-config/select-mysql-utilities	select	none | debconf-set-selections
#DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config_0.5.3-1_all.deb
#fi


apt-add-repository ppa:ubuntu-toolchain-r/test -y

apt-get update

set -x

# Set args to install postfix automagically
debconf-set-selections <<< "postfix postfix/mailname string ${HOSTNAME}"
debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
# install all the deps
DEBIAN_FRONTEND=noninteractive apt-get install -y postfix apache2 php5 php5-dev php-pear make libapache2-mod-php5 pwgen python-setuptools vim-tiny php5-mysql curl wget apache2-mpm-itk curl libcurl3 libcurl3-dev php5-curl php-pear mysql-client php-mail-mimedecode php5-gd php5-intl gettext ntp gcc-4.8 g++-4.8
#printf "\n" | sudo pecl install mongo

echo "### Make sure gcc 4.8 is used..."


update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.8 20
update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 20
update-alternatives --config gcc
update-alternatives --config g++

gcc --version

echo "### Add Locales..."

locale-gen zh_CN zh_CN.UTF-8
locale-gen zh_TW zh_TW.UTF-8
locale-gen de_DE de_DE.UTF-8
locale-gen es_ES es_ES.UTF-8
locale-gen fi_FI fi_FI.UTF-8
locale-gen fr_FR fr_FR.UTF-8
locale-gen it_IT it_IT.UTF-8
locale-gen ja_JP ja_JP.UTF-8
locale-gen nl_NL nl_NL.UTF-8
locale-gen pt_PT pt_PT.UTF-8
locale-gen ru_RU ru_RU.UTF-8
locale-gen sv_SE sv_SE.UTF-8


echo "### Copying PHP settings"
cp /vagrant/conf/php.ini /etc/php5/apache2
cp /vagrant/conf/php-cli.ini /etc/php5/cli/php.ini

echo "### Copying Apache2 Configs"
a2dissite *default*
case "$PROVISION_TYPE" in
	local)
		cp /vagrant/conf/apache/local /etc/apache2/sites-available/local
		cp /vagrant/conf/apache/down.local /etc/apache2/sites-available/down.local
		cp /vagrant/conf/apache/local /etc/apache2/sites-available/local
		a2ensite local
		;;
	plano)
		cp /vagrant/conf/apache/plano.todo-cloud.com /etc/apache2/sites-available/plano.todo-cloud.com
		cp /vagrant/conf/apache/down.plano.todo-cloud.com /etc/apache2/sites-available/down.plano.todo-cloud.com
		a2ensite plano.todo-cloud.com

		mv /vagrant/conf/certs/todo-cloud.com.crt /etc/ssl/certs/
		mv /vagrant/conf/certs/gd_bundle.crt /etc/ssl/certs/
		# Set up the todo-cloud.com SSL private key
		mv /vagrant/conf/certs/todo-cloud.com.key /etc/ssl/private/todo-cloud.com.key
		chown root:root /etc/ssl/private/todo-cloud.com.key
		chmod 600 /etc/ssl/private/todo-cloud.com.key

		# Set up the todopro.com SSL private key
		mv /vagrant/conf/certs/todopro.com.key /etc/ssl/private/todopro.com.key
		chown root:root /etc/ssl/private/todopro.com.key
		chmod 600 /etc/ssl/private/todopro.com.key

		;;
	beta)
		cp /vagrant/conf/apache/beta.todo-cloud.com /etc/apache2/sites-available/beta.todo-cloud.com
		cp /vagrant/conf/apache/siri.todo-cloud.com /etc/apache2/sites-available/siri.todo-cloud.com
		cp /vagrant/conf/apache/sync.todo-cloud.com /etc/apache2/sites-available/sync.todo-cloud.com
		a2ensite beta.todo-cloud.com
		a2ensite siri.todo-cloud.com
		a2ensite sync.todo-cloud.com

		mv /vagrant/conf/certs/todo-cloud.com.crt /etc/ssl/certs/
		mv /vagrant/conf/certs/gd_bundle.crt /etc/ssl/certs/
		# Set up the todo-cloud.com SSL private key
		mv /vagrant/conf/certs/todo-cloud.com.key /etc/ssl/private/todo-cloud.com.key
		chown root:root /etc/ssl/private/todo-cloud.com.key
		chmod 600 /etc/ssl/private/todo-cloud.com.key

		# Set up the todopro.com SSL private key
		mv /vagrant/conf/certs/todopro.com.key /etc/ssl/private/todopro.com.key
		chown root:root /etc/ssl/private/todopro.com.key
		chmod 600 /etc/ssl/private/todopro.com.key

		;;
	production_auth)
		cp /vagrant/conf/apache/auth.appigo.com /etc/apache2/sites-available/auth.appigo.com
		a2ensite auth.appigo.com

		mv /vagrant/conf/certs/appigo.com.crt /etc/ssl/certs/
		mv /vagrant/conf/certs/gd_bundle.crt /etc/ssl/certs/
		# Set up the appigo.com SSL private key
		mv /vagrant/conf/certs/appigo.com.key /etc/ssl/private/appigo.com.key
		chown root:root /etc/ssl/private/appigo.com.key
		chmod 600 /etc/ssl/private/appigo.com.key

		# The auth.appigo.com server
		;;
	production_main)
		# The standard servers behind the load balancer
		cp /vagrant/conf/apache/siri.todo-cloud.com /etc/apache2/sites-available/siri.todo-cloud.com
		cp /vagrant/conf/apache/siri.todopro.com /etc/apache2/sites-available/siri.todopro.com
		cp /vagrant/conf/apache/sync.todo-cloud.com /etc/apache2/sites-available/sync.todo-cloud.com
		cp /vagrant/conf/apache/sync.todopro.com /etc/apache2/sites-available/sync.todopro.com
		cp /vagrant/conf/apache/todo-cloud.com /etc/apache2/sites-available/todo-cloud.com
		cp /vagrant/conf/apache/todopro.com /etc/apache2/sites-available/todopro.com

		cp /vagrant/conf/apache/down.sync.todo-cloud.com /etc/apache2/sites-available/down.sync.todo-cloud.com
		cp /vagrant/conf/apache/down.sync.todopro.com /etc/apache2/sites-available/down.sync.todopro.com
		cp /vagrant/conf/apache/down.todo-cloud.com /etc/apache2/sites-available/down.todo-cloud.com
		cp /vagrant/conf/apache/down.todopro.com /etc/apache2/sites-available/down.todopro.com

		a2ensite siri.todo-cloud.com
		a2ensite siri.todopro.com
		a2ensite sync.todo-cloud.com
		a2ensite sync.todopro.com
		a2ensite todo-cloud.com
		a2ensite todopro.com
		a2ensite default

		mv /vagrant/conf/certs/todo-cloud.com.crt /etc/ssl/certs/
		mv /vagrant/conf/certs/todopro.com.crt /etc/ssl/certs/
		mv /vagrant/conf/certs/gd_bundle.crt /etc/ssl/certs/
		# Set up the todo-cloud.com SSL private key
		mv /vagrant/conf/certs/todo-cloud.com.key /etc/ssl/private/todo-cloud.com.key
		chown root:root /etc/ssl/private/todo-cloud.com.key
		chmod 600 /etc/ssl/private/todo-cloud.com.key

		# Set up the todopro.com SSL private key
		mv /vagrant/conf/certs/todopro.com.key /etc/ssl/private/todopro.com.key
		chown root:root /etc/ssl/private/todopro.com.key
		chmod 600 /etc/ssl/private/todopro.com.key

		;;
esac

a2enmod ssl rewrite headers proxy proxy_http
service apache2 restart

echo "### Apache2 Configured"

case "$PROVISION_TYPE" in
	local)
		echo "### Setting up local MySQL..."
		DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
		service mysql stop
		cp /vagrant/conf/my.cnf /etc/mysql
		service mysql start
		mysql -u root -e "CREATE DATABASE todoonline_db; grant all on *.* to 'root'@'%'; flush privileges; "
		mysql -u root todoonline_db < /vagrant/conf/db/plano_data.sql
		mysql -u root -e "SET PASSWORD FOR 'root'@'%' = PASSWORD('GaueXm264mq68tc66kvnHt4g63Dz');"
		echo "### MySQL Server installed"

		cp /vagrant/conf/DBCredentials-local.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		;;
	plano)
		echo "### Setting up local MySQL..."
		DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
		service mysql stop
		cp /vagrant/conf/my.cnf /etc/mysql
		service mysql start
		mysql -u root -e "SET GLOBAL max_connect_errors=1000;"
		mysql -u root -e "CREATE DATABASE todoonline_db; grant all on *.* to 'root'@'%'; flush privileges; "
		mysql -u root todoonline_db < /vagrant/conf/db/plano_data.sql
		mysql -u root -e "SET PASSWORD FOR 'root'@'%' = PASSWORD('GaueXm264mq68tc66kvnHt4g63Dz');"
		echo "### MySQL Server installed"

		cp /vagrant/conf/DBCredentials-local.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		;;
	beta)
		#cp /vagrant/conf/DBCredentials-production.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		cp /vagrant/conf/DBCredentials-production.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		;;
	production_auth)
		cp /vagrant/conf/DBCredentials-production.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		;;
	production_main)

		cp /vagrant/conf/DBCredentials-production.php /vagrant/www/todo_root/TodoOnline/DBCredentials.php
		;;
	*)
		echo "### UNKNOWN PROVISION TYPE";
		exit 1
		;;
esac


echo "### Other Configuration..."


cp /vagrant/conf/version.php /vagrant/www/todo_root/TodoOnline/version.php

case "$PROVISION_TYPE" in
	local)
		cp /vagrant/conf/config-local.php /vagrant/www/todo_root/TodoOnline/config.php
		echo "define('TDO_REVISION', 'LOCAL');" >> /vagrant/www/todo_root/TodoOnline/version.php
		;;
	plano)
		cp /vagrant/conf/config-plano.php /vagrant/www/todo_root/TodoOnline/config.php
		echo "define('TDO_REVISION', '$GIT_REVISION');" >> /vagrant/www/todo_root/TodoOnline/version.php

		cat /vagrant/conf/authorized_keys-plano >> /home/ubuntu/.ssh/authorized_keys
		;;
	beta)
		cp /vagrant/conf/config-beta.php /vagrant/www/todo_root/TodoOnline/config.php
		echo "define('TDO_REVISION', '$GIT_REVISION');" >> /vagrant/www/todo_root/TodoOnline/version.php
		;;
	production_auth)
		cp /vagrant/conf/config-auth.php /vagrant/www/todo_root/TodoOnline/config.php
		echo "define('TDO_REVISION', '$GIT_REVISION');" >> /vagrant/www/todo_root/TodoOnline/version.php
		;;
	production_main)
		cp /vagrant/conf/config-production.php /vagrant/www/todo_root/TodoOnline/config.php
		echo "define('TDO_REVISION', '$GIT_REVISION');" >> /vagrant/www/todo_root/TodoOnline/version.php
		;;
esac


echo "### Configuring Mail Server..."

case "$PROVISION_TYPE" in
	local)
		;;
	plano)
		useradd -Um --home-dir /var/spool/filter --shell /bin/false mailfilter
		cp /vagrant/conf/postfix/virtual /etc/postfix/
		postmap /etc/postfix/virtual
		cp /vagrant/conf/postfix/auth-master.cf /etc/postfix/master.cf
		cp /vagrant/www/todo_root/tdo-mailhandler.php /usr/local/bin/tdo-mailhandler.php
		chown mailfilter:root /usr/local/bin/tdo-mailhandler.php

		# Add todo-cloud.com and todopro.com so that we can service task creation emails
		sed -i 's/mydestination = /mydestination = plano.todo-cloud.com,todo-cloud.com,todopro.com,/g' /etc/postfix/main.cf

		#The following values are the default
		#echo "smtpd_relay_restrictions = permit_mynetworks permit_sasl_authenticated defer_unauth_destination" >> /etc/postfix/main.cf
		echo "relayhost = email-smtp.us-east-1.amazonaws.com:25" >> /etc/postfix/main.cf
		echo "smtp_sasl_auth_enable = yes" >> /etc/postfix/main.cf
		echo "smtp_sasl_security_options = noanonymous" >> /etc/postfix/main.cf
		echo "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd" >> /etc/postfix/main.cf
		echo "smtp_use_tls = yes" >> /etc/postfix/main.cf
		echo "smtp_tls_security_level = encrypt" >> /etc/postfix/main.cf
		echo "smtp_tls_note_starttls_offer = yes" >> /etc/postfix/main.cf
		echo "virtual_alias_maps = hash:/etc/postfix/virtual" >> /etc/postfix/main.cf

		echo "# configure postfix to use this certificate (so Amazon's secure conncetion is accepted)" >> /etc/postfix/main.cf
		echo "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt" >> /etc/postfix/main.cf

		# Set up the sasl password file
		echo "email-smtp.us-east-1.amazonaws.com:25 AKIAIGQQP3NI34Z3T5ZQ:Aj9Grnx9mDq0A04VpQH8Uh/sINbobRR8ldtLvx3m19zO" > /etc/postfix/sasl_passwd
		# The password is plain text, so make sure only the root user can read it
		chown root:root /etc/postfix/sasl_passwd
		chmod 600 /etc/postfix/sasl_passwd
		postmap hash:/etc/postfix/sasl_passwd

		service postfix reload
		;;
	beta)
		# Set up the server so that it will be able to send outgoing emails
		# via Amazon SES.
		echo "relayhost = email-smtp.us-east-1.amazonaws.com:25" >> /etc/postfix/main.cf
		echo "smtp_sasl_auth_enable = yes" >> /etc/postfix/main.cf
		echo "smtp_sasl_security_options = noanonymous" >> /etc/postfix/main.cf
		echo "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd" >> /etc/postfix/main.cf
		echo "smtp_use_tls = yes" >> /etc/postfix/main.cf
		echo "smtp_tls_security_level = encrypt" >> /etc/postfix/main.cf
		echo "smtp_tls_note_starttls_offer = yes" >> /etc/postfix/main.cf

		echo "# configure postfix to use this certificate (so Amazon's secure conncetion is accepted)" >> /etc/postfix/main.cf
		echo "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt" >> /etc/postfix/main.cf

		# Set up the sasl password file
		echo "email-smtp.us-east-1.amazonaws.com:25 AKIAIGQQP3NI34Z3T5ZQ:Aj9Grnx9mDq0A04VpQH8Uh/sINbobRR8ldtLvx3m19zO" > /etc/postfix/sasl_passwd
		# The password is plain text, so make sure only the root user can read it
		chown root:root /etc/postfix/sasl_passwd
		chmod 600 /etc/postfix/sasl_passwd
		postmap hash:/etc/postfix/sasl_passwd

		service postfix reload
		;;
	production_auth)

		useradd -Um --home-dir /var/spool/filter --shell /bin/false mailfilter
		cp /vagrant/conf/postfix/virtual /etc/postfix/
		postmap /etc/postfix/virtual
		cp /vagrant/conf/postfix/auth-master.cf /etc/postfix/master.cf
		cp /vagrant/www/todo_root/tdo-mailhandler.php /usr/local/bin/tdo-mailhandler.php
		chown mailfilter:root /usr/local/bin/tdo-mailhandler.php

		# Add todo-cloud.com and todopro.com so that we can service task creation emails
		sed -i 's/mydestination = /mydestination = newtask.todo-cloud.com,todo-cloud.com,newtask.todopro.com,todopro.com,/g' /etc/postfix/main.cf

		#The following values are the default
		#echo "smtpd_relay_restrictions = permit_mynetworks permit_sasl_authenticated defer_unauth_destination" >> /etc/postfix/main.cf
		sed -i 's/relayhost =/relayhost = email-smtp.us-east-1.amazonaws.com:25/g' /etc/postfix/main.cf
		#echo "relayhost = email-smtp.us-east-1.amazonaws.com:25" >> /etc/postfix/main.cf
		echo "smtp_sasl_auth_enable = yes" >> /etc/postfix/main.cf
		echo "smtp_sasl_security_options = noanonymous" >> /etc/postfix/main.cf
		echo "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd" >> /etc/postfix/main.cf
		echo "smtp_use_tls = yes" >> /etc/postfix/main.cf
		echo "smtp_tls_security_level = encrypt" >> /etc/postfix/main.cf
		echo "smtp_tls_note_starttls_offer = yes" >> /etc/postfix/main.cf
		echo "virtual_alias_maps = hash:/etc/postfix/virtual" >> /etc/postfix/main.cf

		echo "# configure postfix to use this certificate (so Amazon's secure conncetion is accepted)" >> /etc/postfix/main.cf
		echo "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt" >> /etc/postfix/main.cf

		# Set up the sasl password file
		echo "email-smtp.us-east-1.amazonaws.com:587 AKIAIGQQP3NI34Z3T5ZQ:Aj9Grnx9mDq0A04VpQH8Uh/sINbobRR8ldtLvx3m19zO" > /etc/postfix/sasl_passwd
		# The password is plain text, so make sure only the root user can read it
		chown root:root /etc/postfix/sasl_passwd
		chmod 600 /etc/postfix/sasl_passwd
		postmap hash:/etc/postfix/sasl_passwd

		service postfix reload
		;;
	production_main)
		# Set up the server so that it will be able to send outgoing emails
		# via Amazon SES.
		echo "relayhost = email-smtp.us-east-1.amazonaws.com:25" >> /etc/postfix/main.cf
		echo "smtp_sasl_auth_enable = yes" >> /etc/postfix/main.cf
		echo "smtp_sasl_security_options = noanonymous" >> /etc/postfix/main.cf
		echo "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd" >> /etc/postfix/main.cf
		echo "smtp_use_tls = yes" >> /etc/postfix/main.cf
		echo "smtp_tls_security_level = encrypt" >> /etc/postfix/main.cf
		echo "smtp_tls_note_starttls_offer = yes" >> /etc/postfix/main.cf

		echo "# configure postfix to use this certificate (so Amazon's secure conncetion is accepted)" >> /etc/postfix/main.cf
		echo "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt" >> /etc/postfix/main.cf

		# Set up the sasl password file
		echo "email-smtp.us-east-1.amazonaws.com:25 AKIAIGQQP3NI34Z3T5ZQ:Aj9Grnx9mDq0A04VpQH8Uh/sINbobRR8ldtLvx3m19zO" > /etc/postfix/sasl_passwd
		# The password is plain text, so make sure only the root user can read it
		chown root:root /etc/postfix/sasl_passwd
		chmod 600 /etc/postfix/sasl_passwd
		postmap hash:/etc/postfix/sasl_passwd

		service postfix reload
		;;
esac

echo "### Disable session save path for PHP so the server doens't run out of inodes..."
# Fixes, among other things, a bug where users couldn't upload their profile
# photo on Todo Cloud because the server didn't have any inodes to store the
# temporary uploaded photo for processing: https://github.com/Appigo/todo-issues/issues/948
sed -i 's/^session\.save_path/;session\.save_path/g' /etc/php5/apache2/php.ini

if [ "$SET_UP_DAEMONS" == "true" ]
then
	echo "### Setting up daemons... ";

	# Make the necessary log folders
	mkdir -p /var/log/todo-cloud/
	chown ubuntu:ubuntu -R /var/log/todo-cloud/

	# Link our daemons
	ln -s /vagrant/www/todo_root/TodoOnline/notifydaemons/tdonotifyd /etc/init.d/tdonotifyd 
	ln -s /vagrant/www/todo_root/TodoOnline/notifydaemons/tdoslacknotifyd /etc/init.d/tdoslacknotifyd
	ln -s /vagrant/www/todo_root/TodoOnline/sessiondaemons/tdosessiond /etc/init.d/tdosessiond
	ln -s /vagrant/www/todo_root/TodoOnline/subscriptiondaemons/tdosubscriptiond /etc/init.d/tdosubscriptiond
	ln -s /vagrant/www/todo_root/TodoOnline/onboardingdaemons/tdoonboardingd /etc/init.d/tdoonboardingd

	# Install monit to monitor the daemon processes so that they will
	# automatically be restarted if for whatever reason they fail.
	echo "Installing monit...";
	apt-get install -y monit

	ln -s /vagrant/conf/todo-cloud-monit.conf /etc/monit/conf.d/todo-cloud-monit.conf

	# Start em up
	#service tdonotifyd start 
	#service tdosessiond start
	#service tdosubscriptiond start 

	# Have the monit process start the daemons
	service monit restart


	echo "### Daemons installed and running"
fi

if [ "$PROVISION_TYPE" == "local" ]
then
	echo "### Installing npm and node.js..."
	# The version of npm and node.js available to Ubuntu 14.04 is pretty old,
	# so this allows us to get something WAY more current and stable.
	curl -sL https://deb.nodesource.com/setup_6.x | bash -
	apt-get install -y nodejs

	# Install all the needed node packages
	pushd /vagrant/api
	npm install
	popd

	push /vagrant/api/functions
	npm install
	popd

	# Adding a script to run the node.js server as a service
	cp /vagrant/conf/todo-api.conf /etc/init/
	start todo-api
fi

if [ "$PROVISION_TYPE" == "plano" ]
then
	echo "### Installing npm and node.js..."
	# The version of npm and node.js available to Ubuntu 14.04 is pretty old,
	# so this allows us to get something WAY more current and stable.
	curl -sL https://deb.nodesource.com/setup_4.x | bash -
	apt-get install -y nodejs

	# Install all the needed node packages
	pushd /vagrant/api
	npm install
	popd

	push /vagrant/api/functions
	npm install
	popd

	# Adding a script to run the node.js server as a service
	cp /vagrant/conf/todo-api.conf /etc/init/
	start todo-api
fi

if [ "$PROVISION_TYPE" != "local" ]
then
	ec2metadata |grep public-hostname
	ec2metadata |grep public-ipv4
fi

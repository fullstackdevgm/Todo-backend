---------------------------------------
Todo Cloud setup file
---------------------------------------

This file has the instructions to get your environment set up and running on 
OS X and Amazon Servers (lower in the file)

I. OSX Mavericks and Mountain Lion
---------------------------------------
Apached web sharing is no longer included so you can either use the command line and do this:
    sudo apachectl start

    sudo apachectl stop

    sudo apachectl restart

or you can download this control panel to do it:
http://clickontyler.com/blog/2012/02/web-sharing-mountain-lion/


1. Edit /private/etc/apache2/httpd.conf
---------------------------------------
Un-comment the following line:
LoadModule php5_module libexec/apache2/libphp5.so
	
2. Edit your user's conf file
---------------------------------------

Look in the directory /private/etc/apache2/users for a <user>.conf file.  The 
file should have the same name as your user.  If the file doesn't exist, create
the file using the same name as your users home folder.  If you had the file,
edit the file and put the following contents in it.  If not just put this
in the file:

<Directory "/Users/<user>/Sites/">
    Options FollowSymLinks Indexes MultiViews
    AllowOverride None
    Order allow,deny
    Allow from all
</Directory>


Make sure to replace <user> above with your user's name.

3. Copy /private/etc/php.ini.default to /private/etc/php.ini
---------------------------------------

4. Add your SVN checkout of Todo Cloud to the php include path.
---------------------------------------
Look for the line:
;include_path = ".:/php/includes:/Users/calvin/code/todoonline"
un-comment it (remove the semi-colon at the beginning) and append the full
path to the Todo Cloud base project from your svn checkout.  Mine looks like
this:

include_path = ".:/php/includes:/Users/calvin/code/todoonline:/Users/calvin/code/todoonline/frameworks:/Users/calvin/code/todoonline/frameworks/pear"

Then modify the following settings found in the file

log_errors=On
display_errors=On
mysql.default_socket=/tmp/mysql.sock
pdo_mysql.default_socket=/tmp/mysql.sock
date.timezone=‘America/Denver’
session.save_path = "/tmp"

5. Install Mysql
---------------------------------------
Download and install from: http://www.mysql.com/downloads/mysql
(I installed the 64 bit version for 10.6)
Install both packages in the DMG so you can get it to startup on boot


6. Create symbolic links to the local entry points
---------------------------------------
cd into your Sites folder (cd ~/Sites)

create symbolic links to the docroot folders located in the todo cloud source code.

ln -s <full path to source root>/docroot/todopro.com todo-cloud
ln -s <full path to source root>/docroot/pigeon47 pigeon47
ln -s <full path to source root>/docroot/sync sync
ln -s <full path to source root>/docroot/auth auth

on mine the links look like this:

ln -s /Users/calvin/code/todopro/docroot/todopro.com todo-cloud


7. Set up Siri
---------------------------------------

1. Create a new directory: ~/Sites/siri
note: this isn't a symbolic link because you need to customize the index.php file
for your machine and a sym link would change the source file in your svn checkout

2. cp <path to source code>/docroot/siri.todopro.com/index.php ~/Sites/siri/

3. Modify the siri/index.php to include (use your own username):

	$baseUri = '/~calvin/siri/index.php';

4. Access the CalDav server in your web browser with the following URL:

	http://localhost/~calvin/siri/index.php
    
    (this will only work after you've created a user in your local todo-cloud setup)
	
5. Add a CalDAV calendar/service to your favorite CalDAV client with this Server
   URL:

	http://localhost/~calvin/siri/index.php


8. Turn on or restart web sharing in preferences
---------------------------------------

sudo apachectl restart


9. Create or re-create local Database
---------------------------------------

go to http://localhost/~<username>/pigeon47/manageDB.php

create the database if it hasn't been created or you can remove and re-create it

10. Site is up and running, create a user and login
---------------------------------------

You should now be able to hit the Todo Cloud site on your local machine using

http://localhost/~<username>/todo-cloud

and the Admin console at (the default login for admin is pigeon/hotdog

http://localhost/~<username>/pigeon47

the Authentication (for support at)

http://localhost/~<username>/auth

the Sync interface (this won't do much without authentication)

http://localhost/~<username>/sync


    
    
---------------------------------------
Todo Cloud setup (for Amazon Servers)
---------------------------------------

These are not exact instructions but areas where the server is configured

II. Amazon hosted server
---------------------------------------

1. Create a new instance using the Ubuntu Server 12.04 LTS AMI (64 bit)
---------------------------------------
- set up keypairs so you can ssh into the box
- set up security to lock down (ssh, http, https only)

2. Add LAMP stack (although you only need apache and PHP unless it's a dev server)
---------------------------------------
sudo apt-get install php5 php-mysqlnd 

3. Get the todopro code out to the box.
--------------------------------------
copy the TodoOnline folder out to /usr/share/php/TodoOnline
copy the content of frameworks folder out to /usr/share/php/
set up the docroot folders in /var/www

4. Configure virtual hosts in apache to point to each docroot folder
--------------------------------------
- todo-cloud.com
- todopro.com
- auth.appigo.com (depending on server)
- siri.todo-cloud.com

5. Include todopro and frameworks in php include path
---------------------------------------
Edit the /etc/php5/apache2/php.ini
    change the include_path to include /usr/share/php, usr/share/php/TodoOnline

6. edit the /usr/share/php/TodoOnline config files for the machine
---------------------------------------
DBCredentials.php to point to the instance of mysql

7. create the Database
---------------------------------------

hit you site at https://baseURL/pigeon47/manageDB.php

create or remove the database

8. Production machine
---------------------------------------
Remove the manageDB.php file from the server
(note, the rollout scripts already do this)



--------------------------------------------------------------------------------
Incoming Mail Server for Task Creation (newtask.todo-cloud.com)
--------------------------------------------------------------------------------
Configuration for newtask.todo-cloud.com

Enable SSH to launch at startup:

	update-rc.d ssh start 16 2 3 4 5 . stop 84 0 1 6 .

Copy out the public keys for Boyd and Calvin (found on our other servers)

apt-get update
apt-get install postfix
	Choose "Internet Site" on the first screen
	System mail name: newtask.todo-cloud.com
apt-get install php5
apt-get install php-mail-mimedecode
apt-get install php5-curl

Create a dummy user that will be used to process all incoming mail named "filter"

	useradd -Um --home-dir /var/spool/filter --shell /bin/false mailfilter

Set up a "Catch-All" to let any RECIPIENT value be passed through.  Create /etc/postfix/virtual and add:

	@todomail.appigo.com mailfilter
	@newtask.todopro.com mailfilter
	@test.todopro.com mailfilter
	@newtask.todo-cloud.com mailfilter

postmap /etc/postfix/virtual

Edit /etc/postfix/main.cf and make sure to add:

	virtual_alias_maps = hash:/etc/postfix/virtual

Edit /etc/postfix/master.cf
Change the "smtp" line to be:
smtp    inet    n    -    -    -    -    smtpd -o content_filter=tdo
Below, add a new line with the following
tdo       unix  -       n       n       -       10      pipe flags=XORq user=mailfilter argv=/usr/bin/php -q /usr/local/bin/tdo-mailhandler.php ${sender} ${original_recipient}


Copy the "tdo-mailhandler.php" (from todoonline SVN) to the server's /usr/local/bin/tdo-mailhandler.php

Edit the top of the file as needed to use the right server URL.

chown mailfilter:root /usr/local/bin/tdo-mailhandler.php

Reload POSTFIX with:

	service postfix reload
	
	
	
	
=====================================
 AWS Command-Line Tool for s3rollout
=====================================

1. Download the latest version of the AWS Command-Line Interface Tool

	https://github.com/aws/aws-cli/releases
	
2. Install the cmd-line tool

	cd into the directory you unzipped (from the download)
	
	type: sudo python setup.py install
	
3. Set up your AWS Credentials

	aws configure
	
	a. Enter your Access ID
	b. Enter Secret Access Key
	c. Default Region Name: us-east-1
	d. Default output format: (hit return)
	
4. You are now set up to run the s3rollout.sh script!
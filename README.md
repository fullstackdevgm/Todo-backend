# Todo Cloud Development & Deployment
Todo Cloud uses a system called *Vagrant* which allows developers to run the exact same configuration on any development computer. It does this by installing a virtual machine (Ubuntu) on the local machine. The Vagrant system is also used for deploying a brand new production machine into the Amazon Web Services (AWS) environment.

# Prerequisites
1. Download and install VirtualBox: [https://www.virtualbox.org/wiki/Downloads](https://www.virtualbox.org/wiki/Downloads)
2. Download an install Vagrant: [https://www.vagrantup.com/downloads](https://www.vagrantup.com/downloads)

# Developer Machine Testing
Once you create a development environment on your own computer, Todo Cloud will be available in a web browser by accessing: [http://127.0.0.1:8989/](http://127.0.0.1:8989/)

You can access the admin interface by going to: []http://127.0.0.1:8989/pigeon47/](http://127.0.0.1:8989/pigeon47/)

Username: pigeon
Password: hotdog
	
# Starting the Server Locally
Your development files from the GIT Repo will already be mapped into place on the virtual box. To start the Todo Cloud server locally run the following from the main root of the project:

	vagrant up

This will create a brand new server and launch it.

You can SSH into the server by running the following from the root of the project:

	vagrant ssh
	
Stop the server by running:

	vagrant halt

Destroy (delete) the server by running:

	vagrant destroy

To access the server, open the following in your browser:

	http://127.0.0.1:8989/
	
To administer the server, open the following in your browser (username: pigeon, password: hotdog):

	http://127.0.0.1:8989/pigeon47/
	
# Managing the local API server
The API service runs on AWS lambda in production, but when developing locally, there is an nodejs application running an ExpressJS server that runs the API. The url for this server is `http://127.0.0.1:8989/api/v1/`. Handling of requests goes like this:

	[http-client] > [apache] > [express - using the todo-api service]

Apache passes the handling of `/api/v1/` to Express via a Proxy command in `/etc/apache2/sites-enabled/local`.

While the PHP server will automatcially be updated when the PHP files are changed, the ExpressJS server requires a manual management. 

To install the ExpressJS server's dependencies and ensure that it is ready to go, run the following in _both_ `/todo-cloud-web/api/` _and_ in `/todo-cloud-web/api/functions/`:

	npm install

To control the ExpressJS server do the following from the root project directory (after running `vagrant up`):
	
	vagrant ssh
	
Once you see the Linux VM terminal prompt run this to start the ExpressJS server:

	initctl start todo-api
	
To stop the server run:

	initctl stop todo-api
	
When you changes have occurred in the ExpressJS server code run:
	
	initctl restart todo-api
	
Now the API server will run using the current code.

To see server output, including error and exception messages run the following:

	tail -f /var/log/todo-api.log

# Production Rollout

Preparation:

	vagrant box add todocloud-plano conf/vagrant-aws-plano/aws-todocloud-plano.box
	vagrant box add todocloud-beta conf/vagrant-aws-beta/aws-todocloud-beta.box
	vagrant box add todocloud-auth conf/vagrant-aws-auth/aws-todocloud-auth.box
	vagrant box add todocloud-main conf/vagrant-aws-main/aws-todocloud-main.box


# Rolling out a new server to Amazon EC2

*The following works for Plano, Beta, Auth (the incoming mail server, the system that support.appigo.com uses for authentication, the Todo Cloud daemons), and Production (the main web servers that run behind the load balancer). Just replace `plano.sh` with one of: `auth.sh`, `beta.sh`, or `production.sh`.*

1. Delete any existing `.vagrant` directory so that Vagrant won't get confused about which server it's creating
2. Tag the current development release: `git tag -a todo-cloud-web-2.2.0.17`
3. Push the new tag to GitHub: `git push origin --tags`
4. sh plano.sh up (this creates a brand new server in AWS)
5. Log into AWS Management Console, go to EC2, and identify the new server that was just created
6. Give the server a name in AWS that you can easily recognize and include the current version number (e.g. `plano.todo-cloud.com-2.2.0.17`)
7. Wait for Vagrant to finish bringing the server up
8. Go to the `Elastic IPs` portion of EC2 and point the plano IP address to your new server (which is why it's important to give it a recognizable name)
9. Wait for a minute or two for the new server to kick in (check the version number on the About screen): https://plano.todo-cloud.com/
10. Once the new server is up and running, use the EC2 console to **terminate** the old plano server so we don't continue to be charged for having it running
11. Bump the version of the server in the development branch so it's open for future development: `conf/version.php` (don't forget to commit and push the change to GitHub)
12. Save off the `.vagrant` file to something you can refer to later, if needed: `mv .vagrant .vagrant-plano-2.2.0.17`
13. If you need to access this server in the future, move the vagrant file back into place (`mv .vagrant-plano-2.2.0.17 .vagrant`) and then run this script: `sh plano.sh ssh`

**SPECIAL NOTE FOR PRODUCTION WEB SERVERS:**

1. As of 15 May 2015, you will create **three** of these servers. Use a different `.vagrant` file for each one to make it easier to connect to these servers in the future
2. **DO NOT** use an Elastic IP address for these. They are placed behind the www.todo-cloud.com **Load Balancer**.
3. Coordinate the rollout with customers by using a System Notification
4. Make sure to replace the servers in both the **www.todo-cloud.com** and **www.todopro.com** load balancers


# How the build process works (TODO: Need to rewrite, not verified)

1. The process starts by booting the Ubuntu Stem-cell AMI, which essentially has the required private keys and git installed. 
2. Once booted, the UserDetails script is run. This script does the following:
	1. Use the installed private keys to decrypt the sensitive configurations files (database credentials, Stripe keys, etc)
	2. Clone the latest source into `/vagrant` (which conveniently will match our dev setup)
	3. Executes the `/vagrant/conf/setup.sh` script to install Apache, Postfix, etc 
	4. Copies generic configuration files based on lane (vhosts, mail configs, etc)
3. Upon completion, an email is sent to alert you that the box is available and ready to go

All output is logged to `/tmp/install`, with install-specific comments prepended by three hash signs (###)


# Directory/Code Structure
### www/todo_root/TodoOnline

This is the base directory for the PHP code.

### www/todo_root/TodoOnline/php

The main controller files that direct the web app and define all the entry points.

### www/todo_root/TodoOnline/methodhandlers

The files here respond to calls from the client app(s).

### www/todo_root/TodoOnline/syncmethodhandlers

The files here respond to task sync calls from client app(s).

### www/todo_root/TodoOnline/classes (Core Database Classes)

The core database classes are all here. Classes that deal with user accounts, user subscriptions, teams, lists, tasks, etc. are all here. Most of them are prefixed with **TDO** (Todo Online - the original name for Todo Cloud).

### www/todo_root/TodoOnline/admin

Files here implement the admin interface (/pigeon47/) for Todo Cloud. The UI is extremely rudimentary.

### www/todo_root/TodoOnline/content

Files in this directory are responsible for building the HTML/Javascript page content of www.todo-cloud.com (web browser).

### www/todo_root/TodoOnline/****daemons

The system uses various daemons that run on a separate EC2 machine to handle things like:

1. Subscriptions
2. Cleaning up old sessions
3. Sending onboarding emails
4. Send change notification emails/etc.

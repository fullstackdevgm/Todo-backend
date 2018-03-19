# Redirect all HTTP requests to HTTPS
<VirtualHost *:80>
		ServerName plano.todo-cloud.com

        ServerAdmin webmaster@localhost

        DocumentRoot /vagrant/www/todopro.com

        <Directory /vagrant/www/todopro.com/>
                RewriteEngine On
				RewriteCond %{HTTPS} off
				RewriteRule (.*) https://plano.todo-cloud.com%{REQUEST_URI}
        </Directory>
</VirtualHost>

<VirtualHost *:443>
	ServerName plano.todo-cloud.com

	DocumentRoot /vagrant/www/todopro.com

	ServerAdmin admin@appigo.com

	SSLEngine on

	SSLCertificateFile              /etc/ssl/certs/todo-cloud.com.crt
	SSLCertificateKeyFile           /etc/ssl/private/todo-cloud.com.key
	SSLCertificateChainFile         /etc/ssl/certs/gd_bundle.crt

	<Directory /vagrant/www/todopro.com/>
		Options -Indexes FollowSymLinks MultiViews
		AllowOverride None
		Order allow,deny
		allow from all
		Header set Access-Control-Allow-Origin "*"
		Header set Access-Control-Allow-Headers "content-type, authorization"
	</Directory>

	#Alias /sync /vagrant/www/sync
	#<Directory /vagrant/www/sync/>
	#	Options Indexes FollowSymLinks MultiViews
	#	AllowOverride None
	#	Order allow,deny
	#	allow from all
	#</Directory>

	ScriptAlias /cgi-bin/ /usr/lib/cgi-bin/
	<Directory "/usr/lib/cgi-bin">
		AllowOverride None
		Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
		Order allow,deny
		Allow from all
	</Directory>

	ErrorLog /var/log/apache2/error.log

	# Possible values include: debug, info, notice, warn, error, crit,
	# alert, emerg.
	LogLevel warn

	CustomLog /var/log/apache2/access.log combined

	# Make the /api/v1 path get redirected to ExpressJS running with Node.js
	# which is serving up the same API that our production site runs inside
	# of Amazon API Gateway & Lambda. In the case of here, it's accessing the
	# local MySQL instance. In production, it accesses the production
	# database running inside Amazon RDS (Aurora DB).
	ProxyPass /api/v1 http://localhost:8080

</VirtualHost>

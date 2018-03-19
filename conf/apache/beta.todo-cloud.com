# Redirect all HTTP requests to HTTPS
<VirtualHost *:80>
		ServerName beta.todo-cloud.com

        ServerAdmin webmaster@localhost

        DocumentRoot /vagrant/www/todopro.com

        <Directory /vagrant/www/todopro.com/>
                RewriteEngine On
				RewriteCond %{HTTPS} off
				RewriteRule (.*) https://beta.todo-cloud.com%{REQUEST_URI}
        </Directory>
</VirtualHost>

<VirtualHost *:443>
	ServerName beta.todo-cloud.com

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

</VirtualHost>

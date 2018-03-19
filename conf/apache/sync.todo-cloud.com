<VirtualHost *:443>
        ServerName sync.todo-cloud.com

        DocumentRoot /vagrant/www/todopro.com/

        ServerAdmin webmaster@localhost

        SSLEngine on

        SSLCertificateFile              /etc/ssl/certs/todo-cloud.com.crt
        SSLCertificateKeyFile           /etc/ssl/private/todo-cloud.com.key
        SSLCertificateChainFile         /etc/ssl/certs/gd_bundle.crt

        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /vagrant/www/todopro.com/>
                Options -Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
        </Directory>

	#Alias /sync /vagrant/www/sync/
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

        #ErrorLog ${APACHE_LOG_DIR}/error.log
        ErrorLog /var/log/apache2/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog /var/log/apache2/access.log combined

        Alias /doc/ "/usr/share/doc/"
        <Directory "/usr/share/doc/">
                Options Indexes MultiViews FollowSymLinks
                AllowOverride None
                Order deny,allow
                Deny from all
                Allow from 127.0.0.0/255.0.0.0 ::1/128
        </Directory>


</VirtualHost>

<VirtualHost *:80>
        ServerName sync.todo-cloud.com

        DocumentRoot /vagrant/www/todopro.com/

        ServerAdmin webmaster@localhost

        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /vagrant/www/todopro.com/>
                Options -Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
                RewriteEngine On
                RewriteCond %{HTTP:X-Forwarded-Proto} !https
                RewriteRule !/status https://%{SERVER_NAME}%{REQUEST_URI} [L,R]
        </Directory>

        ScriptAlias /cgi-bin/ /usr/lib/cgi-bin/
        <Directory "/usr/lib/cgi-bin">
                AllowOverride None
                Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
                Order allow,deny
                Allow from all
        </Directory>

        #ErrorLog ${APACHE_LOG_DIR}/error.log
        ErrorLog /var/log/apache2/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog /var/log/apache2/access.log combined

        Alias /doc/ "/usr/share/doc/"
        <Directory "/usr/share/doc/">
                Options Indexes MultiViews FollowSymLinks
                AllowOverride None
                Order deny,allow
                Deny from all
                Allow from 127.0.0.0/255.0.0.0 ::1/128
        </Directory>


</VirtualHost>

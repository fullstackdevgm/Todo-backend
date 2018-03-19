<VirtualHost *:443>
		ServerName todopro.com
        ServerAlias www.todopro.com
        ServerAlias beta.todopro.com
		ServerAlias w3.todopro.com
		ServerAlias fb.todopro.com
		ServerAlias pilot.todopro.com

        DocumentRoot /vagrant/www/todopro.com/

        ServerAdmin webmaster@localhost

        SSLEngine on

        SSLCertificateFile              /etc/ssl/certs/todopro.com.crt
        SSLCertificateKeyFile           /etc/ssl/private/todopro.com.key
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
                Redirect / https://www.todo-cloud.com/
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

<VirtualHost *:80>
        ServerName todopro.com
        ServerAlias www.todopro.com
        ServerAlias beta.todopro.com
	ServerAlias w3.todopro.com
	ServerAlias fb.todopro.com
	ServerAlias pilot.todopro.com

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
                Redirect / https://www.todo-cloud.com/
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

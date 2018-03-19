<VirtualHost *:80>

    ServerAdmin webmaster@localhost

    DocumentRoot /vagrant/www/todopro.com
    <Directory /vagrant/www/todopro.com/>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride None
        #Order allow,deny allow from all
    </Directory>

    # Possible values include: debug, info, notice, warn, error, crit, # alert, emerg.
    LogLevel warn
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>



# Redirect all AUTH requests to HTTPS
<VirtualHost *:80>
        ServerName auth.appigo.com
        ServerAlias auth-stage.appigo.com
        DocumentRoot /vagrant/www/auth.appigo.com
        <Directory /vagrant/www/auth.appigo.com/>
                RewriteEngine On
                RewriteCond %{HTTPS} off
                RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}
        </Directory>
</VirtualHost>
<VirtualHost *:80>
	ServerName todo-cloud.com
	DocumentRoot /vagrant/www/auth.appigo.com
	<Directory /vagrant/www/auth.appigo.com/>
		RewriteEngine on
		RewriteRule (.*) https://www.todo-cloud.com%{REQUEST_URI}
	</Directory>
</VirtualHost>

<VirtualHost *:443>
        ServerName auth.appigo.com
        ServerAlias auth-stage.appigo.com 54.166.8.31

        DocumentRoot /vagrant/www/auth.appigo.com/

        ServerAdmin webmaster@localhost
        SSLEngine on

        SSLCertificateFile              /etc/ssl/certs/appigo.com.crt
        SSLCertificateKeyFile           /etc/ssl/private/appigo.com.key
        SSLCertificateChainFile         /etc/ssl/certs/gd_bundle.crt

        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /vagrant/www/auth.appigo.com/>
                Options -Indexes FollowSymLinks MultiViews
                AllowOverride None
                #Order allow,deny
                allow from all
                RewriteEngine On
                RewriteBase /
                RewriteCond %{HTTP_HOST} ^auth.appigo.com [NC]
                RewriteRule ^/(.*) https://auth.appigo.com/$1 [L,R]
#               RewriteCond %{HTTP:X-Forwarded-Proto} !https
#               RewriteRule !/status https://%{SERVER_NAME}%{REQUEST_URI} [L,R]
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

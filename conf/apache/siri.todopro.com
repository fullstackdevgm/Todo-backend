<VirtualHost *:80>
        ServerName siri.todopro.com
		ServerAlias siry.todopro.com
        DocumentRoot /vagrant/www/siri.todopro.com/

        ServerAdmin webmaster@localhost

        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /vagrant/www/siri.todopro.com/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
                RewriteEngine on
		RewriteCond %{HTTP:X-Forwarded-Proto} !https
		RewriteRule !/status https://%{SERVER_NAME}%{REQUEST_URI} [L,R]
                RewriteBase /
                RewriteRule ^principals /index.php/principals/ [L]
                RewriteRule ^principals/(.*) /index.php/principals/$1 [L]
                RewriteRule ^calendars /index.php/calendars/ [L]
                RewriteRule ^calendars/(.*) /index.php/calendars/$1 [L]
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

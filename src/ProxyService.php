<?php
declare(strict_types=1);

/*
addVirtualHost($domain);
//restartApache();
requestSslCertificateForVirtualHost($domain);
removeVirtualHost($domain, true);


addSslVirtualHost($domain);
restartApache($domain);
*/

class ProxyService {
	public function __construct($internetIp) {
	}

	function addVirtualHost($domain) {
		shell_exec("mkdir -p /var/www/virtual/$domain");
		shell_exec("mkdir -p /var/www/virtual/$domain/htdocs");
		$tpl = getTemplate($domain);
		file_put_contents(getApacheConfigFileName($domain), $tpl);
		shell_exec("a2ensite $domain");
		$this->restartApache();
	}

	function removeVirtualHost($domain, $removeHostingDir=false) {
		shell_exec("a2dissite $domain");
		if ($removeHostingDir) {
			shell_exec("rm -fr /var/www/virtual/$domain");
		}
		unlink("/etc/apache2/sites-available/$domain.conf");
		$this->restartApache();
	}

	function restartApache() {
		shell_exec("systemctl restart apache2.service");
	}

	function requestSslCertificateForVirtualHost($domain) {
		//shell_exec("certbot certonly --webroot -w /var/www/virtual/$domain/htdocs -d $domain -d www.$domain");
		shell_exec("certbot certonly --webroot -w /var/www/virtual/$domain/htdocs -d $domain");
	}

	function addSslVirtualHost($domain) {
		$tpl = getTemplateWithSsl($domain);
		file_put_contents(getApacheConfigFileName($domain), $tpl);
	}

	function getApacheConfigFileName($domain) {
		return "/etc/apache2/sites-available/$domain.conf";
	}

	private function addDockerProxy($host, $port, $domain, $listenIp='*') {
		echo "Creating proxy\n";
		$hostIp = $this->hostIp;
$tpl = <<<TPL
<VirtualHost $listenIp:80>
        ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	AllowEncodedSlashes NoDecode
	ProxyPreserveHost On
	ProxyPass "/"  "http://$host:$port/"
	ProxyPassReverse "/"  "http://$host:$port/"
</VirtualHost>
TPL;
		$file = "/etc/apache2/sites-available/$domain.conf";
		file_put_contents($file, $tpl);
		shell_exec("a2ensite $domain");
		$this->restartApache();
	}

	private function removeDockerProxy($domain) {
		shell_exec("a2dissite $domain");
		$file = "/etc/apache2/sites-available/$domain.conf";
		unlink($file);
		shell_exec("systemctl restart apache2.service");
		$this->restartApache();
	}
	


	function getTemplate($domain) {
$tpl = <<<TPL
<VirtualHost *:80>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	#ServerAlias www.$domain $domain
	DocumentRoot /var/www/virtual/$domain/htdocs
	<Directory /var/www/virtual/$domain>
		#Options Indexes FollowSymLinks MultiViews
		Options FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TPL;
	return $tpl;
	}



	function getTemplateWithSsl($domain) {
$tpl = <<<TPL
<VirtualHost *:80>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	RewriteEngine On 
	RewriteCond %{HTTPS}  !=on 
	RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L] 
</VirtualHost>
<VirtualHost *:443>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	DocumentRoot /var/www/virtual/$domain/htdocs
	SSLEngine on
	SSLCertificateFile    /etc/letsencrypt/live/$domain/cert.pem
	SSLCertificateKeyFile /etc/letsencrypt/live/$domain/privkey.pem
	SSLCACertificateFile  /etc/letsencrypt/live/$domain/fullchain.pem
	<FilesMatch "\.(cgi|shtml|phtml|php)$">
			SSLOptions +StdEnvVars
	</FilesMatch>
	<Directory /usr/lib/cgi-bin>
			SSLOptions +StdEnvVars
	</Directory>
	BrowserMatch "MSIE [2-6]" \
			nokeepalive ssl-unclean-shutdown \
			downgrade-1.0 force-response-1.0
	BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown

	<Directory /var/www/virtual/$domain>
		#Options Indexes FollowSymLinks MultiViews
		Options FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TPL;
	return $tpl;
	}
}



<?php
declare(strict_types=1);

/*
addVirtualHost($domain);
restartApache();
requestSslCertificateForVirtualHost($domain);
removeVirtualHost($domain, true);


addSslVirtualHost($domain);
restartApache($domain);
*/

class ProxyService {
	private $log;

	private $proxyPublicIp;

	public function __construct($proxyPublicIp) {
		$this->log = new Logger();
		$this->proxyPublicIp = $proxyPublicIp;
	}

	function addVirtualHost($domain, $listenIp) {
		$dir = "/var/www/virtual/$domain/htdocs";
		echo "\tcreating vhost dir $dir\n";
		passthru("mkdir -p $dir");
		file_put_contents("$dir/index.html", '<html><body><h3>'.$domain.'</h3></body></html>'."\n");


		$vhostConfig = $this->getApacheConfigFileName($domain);
		$tpl = $this->getTemplate($domain, $listenIp);
		$this->log->log("creating vhostConfig $vhostConfig");
		$this->log->log($tpl, 2);
		file_put_contents($vhostConfig, $tpl);

		echo "\tenabling site $domain\n";
		passthru("a2ensite $domain");
		$this->restartApache();
	}

	function removeVirtualHost($domain, $removeHostingDir=false) {
		echo "\tdisabling site $domain\n";
		passthru("a2dissite $domain");
		if ($removeHostingDir) {
			$dir = "/var/www/virtual/$domain";
			echo "\tremoving dir $dir\n";
			passthru("rm -fr $dir");
		}
		$file = "/etc/apache2/sites-available/$domain.conf";
		echo "\tremoving file $file\n";
		unlink($file);
		$this->restartApache();
	}

	function restartApache() {
		echo "\trestarting apache\n";
		//passthru("systemctl restart apache2.service");
		passthru("systemctl reload apache2.service");
	}

	public function requestSslCertificateForVirtualHost($domain) {

		$info = new SslInfo();
		$info->certFile = "/etc/letsencrypt/live/$domain/cert.pem";
		$info->keyFile = "/etc/letsencrypt/live/$domain/privkey.pem";
		$info->chainFile = "/etc/letsencrypt/live/$domain/fullchain.pem";

		//
		$sslKeyEists = is_file($info->keyFile);
		if ($sslKeyEists) {
			return $info;
		}

		//
		$this->addVirtualHost($domain, $this->proxyPublicIp);

		//
		$ret = 0;
		passthru("certbot certonly --webroot -w /var/www/virtual/$domain/htdocs -d $domain", $ret);
		//passthru("certbot certonly --webroot -w /var/www/virtual/$domain/htdocs -d $domain -d www.$domain");
		if ($ret != 0) {
			throw new Exception("Unable to request ssl certificate");
		}
		$this->removeVirtualHost($domain, false);

		return $info;
	}

	function addSslVirtualHost($domain) {
		$tpl = $this->getTemplateWithSsl($domain);
		file_put_contents($this->getApacheConfigFileName($domain), $tpl);
	}

	function getApacheConfigFileName($domain) {
		return "/etc/apache2/sites-available/$domain.conf";
	}

	public function addDockerProxy($host, $port, $domain, $listenIp='*') {
		echo "Creating proxy\n";
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
		passthru("a2ensite $domain");
		$this->restartApache();
	}

	public function removeDockerProxy($domain) {

		// a2dissite
		$avail = "/etc/apache2/sites-enabled/$domain.conf";
		if (file_exists($avail)) {
			passthru("a2dissite $domain");
			$this->restartApache();
		}

		// remove config and restart
		$file = "/etc/apache2/sites-available/$domain.conf";
		if (file_exists($file)) {
			unlink($file);
		}
	}


	public function addSslDockerProxy(string $host, $port, string $domain, string $listenIp='*') {
		echo "Creating proxy\n";
$tpl = <<<TPL
<VirtualHost $listenIp:80>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	RewriteEngine On 
	RewriteCond %{HTTPS}  !=on 
	RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L] 
</VirtualHost>
<VirtualHost $listenIp:443>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain

	AllowEncodedSlashes NoDecode
	ProxyPreserveHost On
	ProxyPass "/"  "http://$host:$port/"
	ProxyPassReverse "/"  "http://$host:$port/"

	SSLEngine on
	SSLCertificateFile    /etc/letsencrypt/live/$domain/cert.pem
	SSLCertificateKeyFile /etc/letsencrypt/live/$domain/privkey.pem
	SSLCACertificateFile  /etc/letsencrypt/live/$domain/fullchain.pem
</VirtualHost>
TPL;
		$file = "/etc/apache2/sites-available/$domain.conf";
		file_put_contents($file, $tpl);
		passthru("a2ensite $domain");
		$this->restartApache();
	}




	function getTemplate($domain, $listenIp='*') {
$tpl = <<<TPL
<VirtualHost $listenIp:80>
	ServerAdmin ricardo@rhamerica.com
	ServerName $domain
	ServerAlias $domain
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






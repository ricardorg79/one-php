<?php
declare(strict_types=1);
class Cycle {

	private $baseDir;
	private $proxyPrivateIp;
	private $dockerHostIp;

	private $proxyService;

	public function __construct($baseDir, $proxyService, $proxyPrivateIp, $dockerHostIp, $baseDomain) {
		$this->baseDir = $baseDir;
		$this->proxyPrivateIp = $proxyPrivateIp;
		$this->dockerHostIp = $dockerHostIp;
		$this->baseDomain = $baseDomain;
		$this->proxyService = $proxyService;
	}

	function checkCommand($args) {
		$prog = $args[0];
		$action = @$args[1];

		switch ($action) {
			case 'start':
			case 'stop':
			case 'restart':
				$name = @$args[2];
				if (empty($name)) {
					$this->displayUsageAndExit($prog);
					break;
				}
				switch ($action) {
					case 'start':
						echo "Starting\n";
						$this->start_process($name);
						break;
					case 'stop':
						echo "Stopping\n";
						$this->stop_process($name);
						break;
					case 'restart':
						echo "Stopping\n";
						$this->stop_process($name);
						echo "Starting\n";
						$this->start_process($name);
						break;
				}
				break;

			case 'proxy':
				$subAction = $args[2];
				switch ($subAction) {
					case 'create':
						if (empty($args[3]) || empty($args[4])) {
							$this->displayUsageAndExit($prog);
							break;
						}
						$this->createProxy($args[3], $this->dockerHostIp, $args[4]);
						break;
					case 'remove':
						if (empty($args[3])) {
							$this->displayUsageAndExit($prog);
							break;
						}
						$this->removeProxy($args[1]);
						break;
					default:
						$this->displayUsageAndExit($prog);
						break;
				}
				break;
			case 'vhost':
				$subAction = $args[2];
				$domain = $args[3];
				if (empty($domain)) {
					$this->displayUsageAndExit($prog);
					break;
				}
				switch ($subAction) {
					case 'create':
						$this->createVirtualHost($args[3]);
						break;
					case 'remove':
						$this->removeVirtualHost($args[3]);
						break;
					default:
						$this->displayUsageAndExit($prog);
						break;
				}
				break;
			case 'list':
				$this->listDefinitions();
				break;
			default:
				$this->displayUsageAndExit($prog);
				break;
		}
	}

	private function listDefinitions() {
		$defDir = $this->getDefDir();
		$d = opendir($defDir);
		while ($f = readdir($d)) {
			if (substr($f, -5) == '.json') {
				echo substr($f, 0, strlen($f) - 5)."\n";
			} else if (substr($f, -4) == '.ini') {
				//echo substr($f, 0, strlen($f) - 4)."\n";
			}
		}
		closedir($d);
	}

	private function displayUsageAndExit($prog) {
		echo "Usage:\n";
		echo "   $prog list\n";
		echo "   $prog start <name>\n";
		echo "   $prog stop <name>\n";
		echo "   $prog restart <name>\n";
		echo "   $prog proxy create <sub-domain> <local-port>\n";
		echo "   $prog proxy remove <sub-domain>\n";
		echo "   $prog vhost create <domain>\n";
		echo "   $prog vhost remove <domain>\n";
		echo "\n";
		exit;
	}

	private function start_process($name) {

		//
		$defDir = $this->getDefDir();

		//
		$hostIp  = $this->dockerHostIp;

		//
		$def = new JsonDefinition($defDir, $name);


		//http
		$params = [];
		$httpPort = 0;
		if ($def->getHttp() > 0) {
			$httpPort = $this->getNextRandPort();
			$http = $def->getHttp();
			$params[] = "-p";
			$params[] = "$hostIp:$httpPort:$http";
		}

		// portMap
		foreach ($def->getPorts() as $port) { // 53:53/udp
			$params[] = "-p";
			$params[] = "$hostIp:$port";
		}

		// volumes
		foreach ($def->getVolumeMap() as $hostDir => $contDir) {
			$hostDir = trim($hostDir);
			$contDir = trim($contDir);
			$params[] = "-v";
			$params[] = escapeshellarg("$hostDir:$contDir");
		}

		// ENV variables
		foreach ($def->getEnvMap() as $key => $val) {
			$params[] = "-e";
			$params[] = escapeshellarg("$key=$val");
		}

		//--cap-add=NET_ADMIN"
		foreach ($def->getCapabilities() as $cap) {
			$params[] = escapeshellarg("--cap-add=$cap");
		}

		$extra_params = implode(' ', $params);

		$name = $def->getName();
		$image = $def->getImage();
		$cmd = "docker run --restart always -d $extra_params --name $name $image";
		echo "$cmd\n";
		passthru("$cmd");

		if (!empty($httpPort > 0)) {
			$this->createProxy($name,$hostIp, $httpPort);
		}
	}

	private function createProxy($subDomain, $host, $port) {
		$domain = "$subDomain.{$this->baseDomain}";
		$this->proxyService->requestSslCertificateForVirtualHost($domain);
		$this->proxyService->addSslDockerProxy($host, $port, $domain, $this->proxyPrivateIp);
	}

	private function removeProxy($subDomain) {
		$domain = "$subDomain.{$this->baseDomain}";
		$this->proxyService->removeVirtualHost($domain, true);
		$this->proxyService->removeDockerProxy($domain);
	}

	private function createVirtualHost($domain) {
		$this->proxyService->addVirtualHost($domain);
	}

	private function removeVirtualHost($domain) {
		$this->proxyService->removeVirtualHost($domain, true);
	}

	private function stop_process($name) {
		//
		exec("docker kill $name", $out);
		exec("docker rm $name", $out);

		//
		$domain = "$name.{$this->baseDomain}";
		$this->proxyService->removeDockerProxy($domain);
	}

	private function getDefDir() {
		return $this->getDir('defs');
	}

	private function getVarDir() {
		return $this->getDir('var');
	}

	private function getDir($name) {
		$defDir = $this->baseDir.DIRECTORY_SEPARATOR.$name;
		if (!is_dir($defDir)) {
			if(!mkdir($defDir, 0775, true)) {
				throw new Exception('Unable to create datadir');
			}
		}
		return $defDir;
	}

	private function getNextRandPort() {
		$config = $this->readWriteVars(function($vars){
			if (empty($vars['nextPort'])) {
				$vars['nextPort'] = 11000;
			} else {
				$vars['nextPort']++;
			}
			return $vars;
		});
		return $config['nextPort'];
	}

	private function readWriteVars($func) {
		$varsFile = $this->getVarDir().DIRECTORY_SEPARATOR.'vars.json';
		$varsFileLock = "$varsFile.lock";

		$fp = fopen($varsFileLock, "w");
		while (!flock($fp, LOCK_EX)) {
			fprintf(STDERR, "%s\n", "Trying to get lock for $varsFileLock");
			sleep(1);
		}
		ftruncate($fp, 0);      // truncate file
		fwrite($fp, "lock");
		fflush($fp);            // flush output before releasing the lock

		$json = '{}';
		if (is_file($varsFile)) {
			$json = file_get_contents($varsFile);
		}
		$config = json_decode($json, true);
		$config = $func($config);
		$json = json_encode($config,  JSON_PRETTY_PRINT);
		file_put_contents($varsFile, $json);

		flock($fp, LOCK_UN);    // release the lock
		fclose($fp);
		return $config;
	}


}











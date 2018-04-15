<?php
declare(strict_types=1);
class Cycle {

	private $baseDir;
	
	private $proxyPublicIp;
	private $proxyPrivateIp;
	private $dockerHostIp;

	private $proxyService;

	public function __construct($baseDir, $proxyPublicIp, $proxyPrivateIp, $dockerHostIp, $baseDomain) {
		$this->baseDir = $baseDir;
		$this->proxyPublicIp = $proxyPublicIp;
		$this->proxyPrivateIp = $proxyPrivateIp;
		$this->dockerHostIp = $dockerHostIp;
		$this->baseDomain = $baseDomain;
		$this->proxyService = new ProxyService();
	}

	function checkCommand($prog, $target, $action) {
		if (empty($action) && $target != 'list') {
			$this->displayUsageAndExit($prog);
		}

		switch ($action) {
			case 'start':
				echo "Starting\n";
				$this->start_process($target);
				break;

			case 'stop':
				echo "Stopping\n";
				$this->stop_process($target);
				break;

			case 'restart':
				echo "Stopping\n";
				$this->stop_process($target);
				echo "Starting\n";
				$this->start_process($target);
				break;

			default:
				if ($target == 'list') {
					$this->listDefinitions();
				}
				else {
					$this->displayUsageAndExit($prog);
				}
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
		echo "   $prog start\n";
		echo "   $prog stop\n";
		echo "   $prog restart\n";
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
		foreach ($def->getPortMap() as $hostPort => $contPort) {
			$hostPort = (int)$hostPort;
			$contPort = (int)$contPort;
			if ($hostPort == 0 || $contPort == 0) {
				throw new Exception("Invalid port.map format");
			}
			$params[] = "-p";
			$params[] = "$hostIp:$hostPort:$contPort";
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

		$extra_params = implode(' ', $params);

		$name = $def->getName();
		$image = $def->getImage();
		$cmd = "docker run --restart always -d $extra_params --name $name $image";
		echo "$cmd\n";
		passthru("$cmd");

		if (!empty($httpPort > 0)) {
			$this->createProxy($name, $httpPort);
		}
	}

	private function createProxy($name, $port) {
		$domain = "$name.{$this->baseDomain}";
		$this->proxyService->addVirtualHost($domain);
		$this->proxyService->requestSslCertificateForVirtualHost($domain);
		$this->proxyService->removeVirtualHost($domain, true);
		$this->proxyService->addDockerProxy($this->hostIp, $port, $domain, $this->proxyPrivateIp);
	}

	private function stop_process($name) {
		passthru("docker kill $name");
		passthru("docker rm $name");
		$this->removeProxy($name);
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











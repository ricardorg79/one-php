<?php
declare(strict_types=1);
class JsonDefinition implements Definition {

	private $name;
	private $image;
	private $http;
	private $portMap;
	private $volumes;
	private $env;
	private $internet;

	public function __construct($defDir, $name) {
		$defFile = $defDir.DIRECTORY_SEPARATOR.$name.'.json';
		if (!is_file($defFile)) {
			throw new Exception("$defFile not found");
		}
		$config  = json_decode(file_get_contents($defFile), true);

		$this->name     = trim(@$config['name']);
		$this->image    = trim(@$config['image']);
		$this->http     = (int) @$config['port.http'];
		$this->portMap  = isset($config['port.map']) ? [] : $config['port.map'];
		$this->volumes  = isset($config['volumes']) ? [] : $config['volumes'];
		$this->env      = isset($config['env']) ? [] : $config['env'];
		$this->internet = (bool)(isset($config['internet']) ? false : $config['internet']);
		if (empty($this->name) || empty($this->image)) {
			throw new Exception("name, image and port are required");
		}
	}

	public function getName() : string {
		return $this->name;
	}

	public function getImage() : string {
		return $this->image;
	}

	public function getHttp() : int {
		return (int) $this->http;
	}

	public function getPortMap() : array {
		return $this->portMap;
	}

	public function getVolumeMap() : array {
		return $this->volumes;
	}

	public function getEnvMap() : array {
		return $this->env;
	}

	public function isInternet() : bool {
		return $this->internet;
	}
}


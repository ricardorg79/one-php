<?php
declare(strict_types=1);
class JsonDefinition implements Definition {

	private $name;
	private $image;
	private $http;
	private $ports;
	private $volumes;
	private $env;
	private $caps;

	public function __construct($defDir, $name) {
		$defFile = $defDir.DIRECTORY_SEPARATOR.$name.'.json';
		if (!is_file($defFile)) {
			throw new Exception("$defFile not found");
		}
		$config  = json_decode(file_get_contents($defFile), true);
		if ($config == null) {
			throw new Exception("Unable to decode JSON from file $defFile");
		}


		$this->name     = trim(@$config['name']);
		$this->image    = trim(@$config['image']);
		$this->http     = (int) @$config['port.http'];
		$this->ports    = array_key_exists('ports', $config) ? $config['ports'] : [];
		$this->volumes  = array_key_exists('volumes', $config) ? $config['volumes'] : [];
		$this->env      = array_key_exists('env', $config) ? $config['env'] : [];
		$this->caps     = array_key_exists('caps', $config) ? $config['caps'] : [];
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

	public function getPorts() : array {
		return $this->ports;
	}

	public function getVolumeMap() : array {
		return $this->volumes;
	}

	public function getEnvMap() : array {
		return $this->env;
	}

	public function getCapabilities() : array {
		return $this->caps;
	}
}


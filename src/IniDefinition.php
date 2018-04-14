<?php
declare(strict_types=1);
class IniDefinition implements Definition {

	private $name;
	private $image;
	private $http;
	private $portMap;
	private $volumes;
	private $env;

	public function __construct($defDir, $name) {
		$defFile = $defDir.DIRECTORY_SEPARATOR.$name.'.ini';
		if (!is_file($defFile)) {
			throw new Exception("$defFile not found");
		}
	}
	private function parseMapConfig($string) {
		$map = [];
		if (!empty($string)) {
			foreach (explode(',', $string) as $colonSeparated) {
				if (strpos($colonSeparated, ':') === false) {
					throw new Exception("Invalid $name format");
				}
				list($left, $right) = explode(':', $colonSeparated);
				$left = trim($left);
				$right = trim($right);
				$map[$left] = $right;
			}
		}
		return $map;
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

	public function getEnvMap() {
		return $this->env;
	}
}


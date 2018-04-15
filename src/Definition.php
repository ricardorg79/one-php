<?php
declare(strict_types=1);
interface Definition {

	public function getName() : string;

	public function getImage() : string;

	public function getHttp() : int;

	public function getPorts() : array;

	public function getVolumeMap() : array;

	public function getEnvMap() : array;

	public function getCapabilities() : array;
}


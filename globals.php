<?php
error_reporting(E_ALL);

$IP="10.10.10.1";


//include(__DIR__.'/funcs.php');
ini_set('include_path',
	__DIR__ . DIRECTORY_SEPARATOR . 'src'
	.PATH_SEPARATOR.
	ini_get('include_path')
);

spl_autoload_register(function ($class_name) {
	$file = str_replace('\\', '/', $class_name).'.php';
	foreach(explode(PATH_SEPARATOR, ini_get('include_path')) as $path) {
		$pathAndFile = $path . DIRECTORY_SEPARATOR . $file;
		//echo "## $pathAndFile ##\n";
		if(is_file($pathAndFile)) {
			include($pathAndFile);
			break;
		}
	}
});



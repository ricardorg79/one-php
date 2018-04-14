<?php

function startStopFunc($argv) {
	global $EXTRA_PARAMS, $NAME, $IMAGE;
	$prog = $argv[0];
	$action = $argv[1];

	switch ($action) {
		case 'start':
			echo "Starting\n";
			start_process($NAME, $IMAGE, $EXTRA_PARAMS);
		break;
		case 'stop':
			echo "Stopping\n";
			stop_process($NAME);
		break;
		case 'restart':
			stop_process($NAME);
			start_process($NAME, $IMAGE, $EXTRA_PARAMS);
		break;
	  	default:
			echo "Usage:\n";
			echo "   $prog start\n";
			echo "   $prog stop\n";
			echo "   $prog restart\n";
			echo "\n";
		break;
	}
}





function start_process($name, $image, $extra_params) {
	$extra = '';
	foreach ($extra_params as $p) {
		$extra .= ' ' . escapeshellarg($p);
	}
	$cmd = "docker run --restart always -d $extra  --name $name $image";

	echo "$cmd\n";
	passthru("$cmd");
}

function stop_process($name) {
	passthru("docker kill $name");
	passthru("docker rm $name");
}


#!/usr/bin/env php
<?php
include(__DIR__.'/util/globals.php');

$NAME = "nexus";
$IMAGE = "sonatype/nexus3";
$EXTRA_PARAMS = [
		"-p", "$IP:8389:8081",
		"-e", 'INSTALL4J_ADD_VM_PARAMS=-Xms256m -Xmx256m -XX:MaxDirectMemorySize=200m -Djava.util.prefs.userRoot=/srv'
	];

startStopFunc($argv);

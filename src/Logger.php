<?php
declare(strict_types=1);
class Logger {
	public function log($block, $indent=1, $padStr="\t") {
		$pad = "";
		for ($i = 0; $i < $indent; $i++) {
			$pad .= $padStr;
		}

		foreach (explode("\n", $block) as $line) {
			echo $pad . trim($line) . "\n";
		}
	}
}

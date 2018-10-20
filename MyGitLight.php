<?php

array_shift($agv);
	if($argc < 1) {
		echo "an argument is expected\n";
	}

	if($argv[0] == "init") {
		array_shift($argv);
		if($argc < 1) {
		 echo "Please specify valid path\n";

		 return 1;
		}
		elseif($fullPath = realpath($argv[0]) && is_dir($fullPath)) {
			mkdir($fullPath . "/MyGitLight", 0777, true);
			copy(__FILE__, $fullPath, "/MyGitLight/MyGitLight.php");
			echo "successfull init\n";
			return 0;
		}
	}

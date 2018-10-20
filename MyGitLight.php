<?php

/* 
	Write an executable program "MyGitLight.php"
	who takes command line,
	the argument "init" followed by a path. 
	MyGitLight will have to copy its own source code and place it in a ".MyGitLight" folder that you have created in the path.
*/

	//TODO Write man
	//TODO add try/catch everywhere

	array_shift($argv);
	if ($argc < 1){
		echo "MyGitLight expects a command and arguments\n";
		//TODO echo man
		return 1;
	}elseif ($argv[0] == "init"){
		array_shift($argv);
		if ($argc < 1){
			echo "Please specify valid path\n";
			//TODO echo man
			return 1;
		} elseif ($fullPath = realpath($argv[0]) && is_dir($fullPath)){
			mkdir($fullPath . "/.MyGitLight", 0777, true);
			copy(__FILE__, $fullPath . "/.MyGitLight/MyGitLight.php");
			echo "Successfull init\n";
			return 0;
		}
	}
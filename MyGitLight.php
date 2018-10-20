<?php

/* 
	Write an executable program "MyGitLight.php"
	who takes command line,
	the argument "init" followed by a path. 
	MyGitLight will have to copy its own source code and place it in a ".MyGitLight" folder that you have created in the path.
*/

	//TODO Write man
	//TODO add try/catch everywhere

	if ($argc < 2){
		feedback("MyGitLight expects a command and arguments");
		return 1;
	} elseif ($argv[1] == "init"){
		if ($argc < 3){
			feedback("Please specify valid path");
			return 1;
		} elseif (($fullPath = realpath($argv[2])) && is_dir($fullPath)){
			if (file_exists($fullPath . "/.MyGitLight")){
				feedback("this folder already has a MyGitLight");
				return 1;
			} else {
				if (!is_writable($fullPath)){
					feedback("could not access folder : bad permissions");
					return 1;
				} else {
					mkdir($fullPath . "/.MyGitLight", 0777, true);
					copy(__FILE__, $fullPath . "/.MyGitLight/MyGitLight.php");
					return 0;
				}
			}
		} else {
			feedback("could not access $argv[2]");
			return 1;
		}
	} elseif ($argv[1] == "add") {
		$paths = ($argv > 3) ? array_slice($argv, 2) : scandir(getcwd());
		foreach ($paths as $origin){
			if (file_exists($origin)){
				copy($origin, dirname(dirname(__FILE__)));
			} else {
				
			}
		}
	} elseif ($argv[1] == "commit") {
		if (argc < 3){
			feedback("A commit message is needed");
			return 1;
		}
	} elseif ($argv[1] == "remove") {
		//TODO
	} elseif ($argv[1] == "log") {
		//TODO
	} else {
		echo $argv[1] . " Isn't a valid command\n";
		return 1;
	}

	function feedback(string $str){
		echo $str . "\n";
		//TODO show man
	}
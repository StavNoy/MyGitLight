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
					echo "Successfull init\n";
					return 0;
				}
			}
		} else {
			feedback("could not access $argv[2]");
			return 1;
		}
	} else {
		echo $argv[1] . " Isn't a valid command\n";
		return 1;
	}

	function feedback(string $str){
		echo $str . "\n";
		//TODO show man
	}
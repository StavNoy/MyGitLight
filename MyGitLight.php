<?php

	//TODO Write man
	//TODO add try/catch everywhere

	if ($argc < 2){
		feedback("MyGitLight expects a command");
		return 1;
	} elseif (function_exists($argv[1])){
		return $argv[1]();
	} else {
		echo $argv[1] . " Isn't a valid command\n";
		return 1;
	}


	function feedback(string $str){
		echo $str . "\n";
		//TODO show man
	}

	function commit(){
		if (argc < 3){
			feedback("A commit message is needed");
			return 1;
		}
	}

	function add(){
		$paths = ($argv > 3) ? array_slice($argv, 2) : scandir(getcwd());
		var_dump($paths);
		foreach ($paths as $origin){
			if (!copy($origin, dirname(__FILE__))){
				echo "Failed to add $origin\n";
			}
		}
		return 0;
	}

	function init(){
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
	}
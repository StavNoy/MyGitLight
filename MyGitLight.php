<?php

	//TODO Write man
	//TODO add try/catch everywhere

	var_dump(array_diff(scandir(getcwd()), array('..', '.')));
	
	if ($argc < 2){
		feedback("MyGitLight expects a command");
		return 1;
	} elseif (function_exists($argv[1])){
		return $argv[1](array_slice($argv, 2));
	} else {
		echo $argv[1] . " Isn't a valid command\n";
		return 1;
	}


	function feedback(string $str){
		echo $str . "\n";
		//TODO show man
	}

	function commit(array $msgAr = []){
		if (empty($msgAr)){
			feedback("A commit message is needed");
			return 1;
		}
	}

	function add(array $paths = []){
		if (empty($paths)) {
			$paths = scandir(getcwd());
			$paths = array_diff($paths, array('..', '.'));
		}
		foreach ($paths as $origin){
			rec_copy($origin, dirname(__FILE__) . "/" . $origin);
		}
		return 0;
	}
	function rec_copy(string $origin, string $target){
		if (!is_dir($origin)){
			if (!copy($origin, $target)){
				echo "Failed to add $origin\n";
			}
		} else {
			$subFiles = scandir($origin);
			$subFiles = array_diff($subFiles, array('..', '.'));
			foreach($subFiles as $aSub){
				rec_copy($aSub, $origin . "/" . $aSub);
			}
		}
	}

	function init(array $args = []){
		if (empty($args)){
			feedback("Please specify valid path");
			return 1;
		} elseif (($fullPath = realpath($args[0])) && is_dir($fullPath)){
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
			feedback("could not access $args[0]");
			return 1;
		}
	}
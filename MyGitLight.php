<?php

	//TODO Write man
	//TODO add try/catch everywhere
	
	if ($argc < 2){
		feedback("MyGitLight expects a command");
		return 1;
	} elseif (function_exists($argv[1])){
		$motherdir = dirname(__FILE__);
		if (basename($motherdir) != ".MyGitLight"){
			echo "Error : not a MyGitLight repository\n";
			return 1;
		} elseif ($argv[1] == "log"){
			return getLog();
	 	} else {
			return $argv[1](array_slice($argv, 2));
		}
	} else {
		echo "'$argv[1]' Isn't a valid command\n";
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
		} else {
			$motherdir = realpath(dirname(__FILE__));
			$tarballs = "$motherdir/tarballs";
			$added = "$motherdir/added";
			$log = "$motherdir/log.txt";
			if(!file_exists($tarballs)) {
				mkdir($tarballs);
			}
			if(is_dir($added)) {
				$logs = file($log);
				$lastLog = $logs[count($logs)-1];
				$id = explode(" ", $lastLog)[0]+1;
				$tarName = "$tarballs/$id.tar";
				$folder = new PharData($tarName);
				$folder->buildFromDirectory($added);
				$compressed = $folder->compress(Phar::GZ);
				unlink($tarName);
				rec_del($added);
				file_put_contents("$motherdir/log.txt", "$id $msgAr[0]\n", FILE_APPEND);
				return 0;
			} else {
				feedback("nothing to add");
				return 1;
			}
		}
	}

	function getLog(){
		$log = dirname(__FILE__) . "/log.txt";
		if (!file_exists($log)){
			echo "log empty\n";
		} else {
			$lines = file($log);
			for ($i = count($lines)-1; $i >= 0 ; $i--) { 
				echo $lines[$i];
			}
		}
		return 1;
	}

	function add(array $paths = []){
		if (empty($paths)) {
			$paths = scandir(getcwd());
			$paths = array_diff($paths, ['..', '.', '.MyGitLight']);
		}
		$motherdir = dirname(__FILE__);
		if(!file_exists("$motherdir/added")){
			mkdir("$motherdir/added");
		}
		foreach ($paths as $origin){
			rec_copy($origin, "$motherdir/added/$origin");
		}
		return 0;
	}
	function rec_copy(string $origin, string $target){
		if (!is_dir($origin)){
			if (!copy($origin, $target)){
				echo "Failed to add $origin\n";
			}
		} else {
			if(!file_exists($target)){
				mkdir($target, 0777, true);
			}
			$subFiles = scandir($origin);
			$subFiles = array_diff($subFiles, ['..', '.']);
			foreach($subFiles as $aSub){
				rec_copy($origin . "/" . $aSub, $target . "/" . $aSub);
			}
		}
	}

	function init(array $args = []){
		if (empty($args)){
			feedback("Please specify valid path");
			return 1;
		} elseif (file_exists($args[0]) && is_dir($args[0])){
			$path = $args[0];
			if (file_exists($path . "/.MyGitLight")){
				feedback("this folder already has a MyGitLight");
				return 1;
			} else {
				if (!is_writable($path)){
					feedback("could not access folder : bad permissions");
					return 1;
				} else {
					mkdir($path . "/.MyGitLight", 0777, true);
					copy(__FILE__, $path . "/.MyGitLight/MyGitLight.php");
					return 0;
				}
			}
		} else {
			feedback("could not access $args[0]");
			return 1;
		}
	}
		function rec_del(string $path){
			if (is_dir($path)){
				$dir = opendir($path);
				while ($subfile = readdir($dir)){
					if (basename($subfile) != "." && basename($subfile) != ".."){
						rec_del($path . "/" . $subfile);
					}
				}
				closedir($dir);
				rmdir($path);
			} else {
				unlink($path);
			}
	}
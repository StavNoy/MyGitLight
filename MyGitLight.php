<?php

	//TODO add try/catch everywhere
	//TODO change from anonymous functions to switch
	//TODO make global variable paths

	define("GIT_FOLDER",	dirname(__FILE__));
	define("GITROOT",		dirname(GIT_FOLDER));
	define("TARBALLS", 		GIT_FOLDER."/tarballs");
	define("ADDED", 		GIT_FOLDER."/added") ;
	define("DELETED",		GIT_FOLDER."/deleted.txt");
	define("LOG",			GIT_FOLDER."/log.txt");
	define("CHECKOUT_TIME", GIT_FOLDER."/last_checkout.txt");

	if ($argc < 2){
		feedback("MyGitLight expects a command");
		return 1;
	} elseif (function_exists($argv[1])){
		if (basename(GIT_FOLDER) != ".MyGitLight"){
			echo "Error : not a MyGitLight repository\n";
			return 1;
		} elseif ($argv[1] == "log"){
			return getLog();
		} else {
			return $argv[1](array_slice($argv, 2));
		}
	} else {
		feedback("'$argv[1]' Isn't a valid command");
		return 1;
	}


	function feedback(string $str){
		echo $str . "\n";
		help();
	}
	function help(){
		echo file_get_contents(dirname(__FILE__)."/man.txt");
	}
	function checkout(array $args){
		if ($args < 1){
			feedback("checkout needs an ID");
			return 1;
		} elseif (filemtime(TARBALLS) <= filemtime(ADDED)){
				feedback("checkout blocked : uncommitted changes");
				return 1;
		} else {
			$id = $args[0];
			$workFiles = scandir(GITROOT);
			$workFiles = array_diff($workFiles, [".", "..", ".MyGitLight"]);
			$last_checkout = filemtime(CHECKOUT_TIME);
			foreach ($workFiles as $file){
				$modTime = filemtime($file);
				if ($modTime > $last_checkout && $modTime >= filemtime(ADDED)){
					feedback("checkout blocked : uncommitted changes");
					return 1;
				}
			}
			$found_archive = FALSE;
			foreach (scandir(TARBALLS) as $tarball){
				if (basename($tarball) == "$id.tar.gz"){
					$found_archive = TARBALLS."/$tarball";
					break;
				}
			}
			if (!$found_archive) {
				feedback("bad id");
				return 1;
			} else {
				foreach ($workFiles as $file) {
					rec_del($file);
				}
				$compressed = new PharData($found_archive);
				$compressed->decompress();
				(new PharData(TARBALLS . "/$id.tar"))->extractTo(GITROOT);
				Phar::unlinkArchive(TARBALLS . "/$id.tar");
				touch(CHECKOUT_TIME);
				return 0;
			}
		}
	}
	function path_from_gitRoot(string $fileName){
		$toRemove = realpath(GITROOT);
		$fullPath = realpath($fileName);
		return substr($fullPath,strlen($toRemove)+1);
	}
	function status(){
		$status = ["modified" => [], "untracked" => [], "deleted" => []];
		$workFiles = scandir(GITROOT);
		$workFiles = array_diff($workFiles, [".", "..", ".MyGitLight"]);
		foreach($workFiles as $file){
			$added_version = ADDED."/$file";
			if (!file_exists($added_version)){
				$status["untracked"][] = "$file at " . path_from_gitRoot($file) . "\n";
			}
			if (filemtime($file) != filemtime($added_version)){
				$status["modified"][] = "$file at " . path_from_gitRoot($file) . "\n";
			}
		}
		$status["deleted"] = file(DELETED);
		foreach ($status as $state => $fileNames){
			sort($fileNames);
			$asString = implode("",$fileNames);
			echo "$state :\n$asString";
		}
		return 0;
	}
	function rm(array $args){
		if (count($args) < 1){
			feedback("Please specify files to remove");
			return 1;
		} else {
			foreach ($args as $path){
				$added_version = ADDED."/$path";
				$work_version = GITROOT."/$path";
				if (file_exists($work_version) && file_exists($added_version)){
					rec_del($added_version);
					rec_del($work_version);
					$toWrite = $path . path_from_gitRoot($path) . "\n";
					file_put_contents(DELETED, $toWrite,FILE_APPEND);
				}else {
					feedback("Stopped : Files must exist in both the working and the tracking directories");
					return 1;
				}
			}
			return 0;
		}
	}

	function commit(array $msgAr = []){
		if (empty($msgAr)){
			feedback("A commit message is needed");
			return 1;
		} else {
			if(!file_exists(TARBALLS)) {
				mkdir(TARBALLS);
			}
			if(!file_exists(ADDED)) {
				mkdir(ADDED);
			}
			if(filemtime(ADDED) > filemtime(TARBALLS)) {
				$id = 1;
				if (file_exists(LOG)){
					$logs = file(LOG);
					$lastLog = $logs[count($logs)-1];
					$id = explode(" ", $lastLog)[0]+1;
				}
				$tarName = TARBALLS."/$id.tar";
				$folder = new PharData($tarName);
				$folder->buildFromDirectory(ADDED);
				$folder->compress(Phar::GZ);
				unlink($tarName);
				file_put_contents(LOG, "$id $msgAr[0]\n", FILE_APPEND);
				return 0;
			} else {
				feedback("nothing to add");
				return 1;
			}
		}
	}

	function getLog(){
		if (!file_exists(LOG)){
			echo "LOG empty\n";
		} else {
			$lines = file(LOG);
			for ($i = count($lines)-1; $i >= 0 ; $i--) {
				echo $lines[$i];
			}
		}
		return 1;
	}

	function add(array $paths = []){
		if (empty($paths)) {
			$paths = scandir(getcwd());
			$paths = array_diff($paths, ['..', '.', '.MyGitLight', 'added']);
		}
		if(!file_exists(ADDED)){
			mkdir(ADDED);
		}
		foreach ($paths as $origin){
			if (file_exists($origin)) {
				rec_copy($origin, ADDED."/$origin");
			}
			else {
				echo "Skipped : $origin is not a valid path\n";
			}
		}
		return 0;
	}
	function rec_copy(string $origin, string $target){
		if (!file_exists($target) || filemtime($origin) < filemtime($target)){
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
	}

	function init(array $args = []){
		if (empty($args)) {
			feedback("Please specify valid path");
			return 1;
		} elseif (!file_exists($args[0]) || !is_dir($args[0])) {
			feedback("could not access $args[0]");
			return 1;
		} else {
			$path = $args[0];
			if (file_exists($path . "/.MyGitLight")) {
				feedback("this folder already has a MyGitLight");
				return 1;
			} else {
				if (!is_writable($path)) {
					feedback("could not access folder : bad permissions");
					return 1;
				} else {
					mkdir($path . "/.MyGitLight", 0777, true);
					copy(__FILE__, $path . "/.MyGitLight/MyGitLight.php");
					copy(dirname(__FILE__) . "/man.txt", $path . "/.MyGitLight/man.txt");
					//TODO copy diff class
					//TODO make added, tarballs, log, deleted, lastcheckout, etc
					return 0;
				}
			}
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
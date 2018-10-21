<?php

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
} elseif($argv[1] == "init" || $argv[1] == "help") {
	return $argv[1](array_slice($argv, 2));
} elseif (basename(GIT_FOLDER) != ".MyGitLight"){
	feedback("Error : not a MyGitLight repository");
	return 1;
}else{
	$args = array_slice($argv, 2);
	switch($argv[1]){
		case "add" :
			return add($args);
		case "commit" :
			return commit($args);
		case "rm" :
			return rm($args);
		case "log" :
			return getlog();
		case "status" :
			return status();
		case "checkout" :
			return checkout($args);
		case "diff" :
			// TODO write function
			return diff();
		default :
			feedback("'$argv[1]' Isn't a valid command");
			return 1;
	}
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
function status(){
	$status = ["modified" => [], "untracked" => [], "deleted" => []];
	$workFiles = scandir(GITROOT);
	$workFiles = array_diff($workFiles, [".", "..", ".MyGitLight"]);
	foreach($workFiles as $file){
		$added_version = ADDED."/$file";
		if (!file_exists($added_version)) {
			$status["untracked"][] = "$file\n";
		} elseif (filemtime(GITROOT."/$file") >= filemtime("$added_version")){
			$status["modified"][] = "$file\n";
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
				$toWrite = "$path " . GITROOT."/$path\n";
				file_put_contents(DELETED, $toWrite,FILE_APPEND);
			}else {
				feedback("Stopped : File '$path' must exist in both the working and the tracking directories");
				return 1;
			}
		}
		return 0;
	}
}

function commit(array $msgAr = []){
	if (empty($msgAr)) {
		feedback("A commit message is needed");
		return 1;
	} elseif (filemtime(ADDED) <= filemtime(TARBALLS)) {
		feedback("nothing to add");
		return 1;
	} else {
		$id = 1;
		if ($logs = file(LOG)) {
			$lastLog = $logs[count($logs) - 1];
			$id = explode(" ", $lastLog)[0] + 1;
		}
		$tarName = TARBALLS . "/$id.tar";
		$folder = new PharData($tarName);
		$folder->buildFromDirectory(ADDED);
		$folder->compress(Phar::GZ);
		unlink($tarName);
		file_put_contents(LOG, "$id $msgAr[0]\n", FILE_APPEND);
		return 0;
	}
}

function getLog(){
	$lines = file(LOG);
	if (empty($lines)) {
		echo "log empty\n";
	} else {
		for ($i = count($lines) - 1; $i >= 0; $i--) {
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
	foreach ($paths as $source){
		if (!file_exists($source)) {
			echo "Skipped : $source is not a valid path\n";
		} else {
			rec_copy($source, ADDED . "/" . basename($source));
		}
	}
	return 0;
}
function rec_copy(string $source, string $dest){
	if (!file_exists($dest) || filemtime($source) < filemtime($dest)){
		if (!is_dir($source)){
			if (!copy($source, $dest)){
				echo "Failed to add $source\n";
			}
		} else {
			if(!file_exists($dest)){
				mkdir($dest);
			}
			$subFiles = scandir($source);
			$subFiles = array_diff($subFiles, ['..', '.']);
			foreach($subFiles as $aSub){
				rec_copy($source . "/" . $aSub, $dest . "/" . $aSub);
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
				$newGitFolder = "$path/.MyGitLight";
				mkdir($newGitFolder, 0777, true);
				copy(__FILE__, "$newGitFolder/MyGitLight.php");
				copy(dirname(__FILE__) . "/man.txt", "$newGitFolder/man.txt");
				copy(dirname(__FILE__) . "/class.Diff.php", "$newGitFolder/class.Diff.php");
				mkdir("$newGitFolder/added");
				mkdir("$newGitFolder/tarballs");
				touch("$newGitFolder/log.txt");
				touch("$newGitFolder/deleted.txt");
				touch("$newGitFolder/last_checkout.txt");
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
function path_from_gitRoot(string $fileName){
	$fullPath = realpath($fileName);
	$toRemove = realpath(GITROOT);
	echo "|f| $fileName |f path| $fullPath |rem| $toRemove\n";
	var_dump($fullPath);
	return substr($fullPath,strlen($toRemove)+1);
}

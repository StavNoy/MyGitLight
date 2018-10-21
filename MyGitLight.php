<?php

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
    function path_from_gitRoot(string $fileName){
	    $toRemove = realpath(dirname(dirname(__FILE__)));
	    $fullPath = realpath($fileName);
	    return substr($fullPath,strlen($toRemove)-1);
    }
	function status(){
	    $status = ["modified" => [], "untracked" => [], "deleted" => []];
        $motherdir = dirname(__FILE__);
        $added = "$motherdir/added";
        $workFiles = scandir("$motherdir/../");
        $workFiles = array_diff($workFiles, [".", "..", ".MyGitLight"]);
        foreach($workFiles as $file){
            $added_version = "$added/$file";
            if (!file_exists($added_version)){
                $status["untracked"][] = "$file at " . path_from_gitRoot($file);
            }
            if (filemtime($file) != filemtime($added_version)){
                $status["modified"][] = "$file\n";
            }
        }
        $status["deleted"] = file("$motherdir/deleted.txt");
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
			$motherdir = dirname(__FILE__);
			$deleted = "$motherdir/deleted.txt";
			foreach ($args as $path){
				$added_version = "$motherdir/added/$path";
				$work_version = "$motherdir/../$path";
				if (file_exists($work_version) && file_exists($added_version)){
					rec_del($added_version);
					rec_del($work_version);
					file_put_contents($deleted,"$path\n",FILE_APPEND);
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
			$motherdir = realpath(dirname(__FILE__));
			$tarballs = "$motherdir/tarballs";
			$added = "$motherdir/added";
			$log = "$motherdir/log.txt";
			if(!file_exists($tarballs)) {
				mkdir($tarballs);
			}
			if(!file_exists($added)) {
				mkdir($added);
			}
			if(filemtime($added) < filemtime($tarballs)) {
				$id = 1;
				if (file_exists($log)){
					$logs = file($log);
					$lastLog = $logs[count($logs)-1];
					$id = explode(" ", $lastLog)[0]+1;
				}
				$tarName = "$tarballs/$id.tar";
				$folder = new PharData($tarName);
				$folder->buildFromDirectory($added);
				$folder->compress(Phar::GZ);
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
			$paths = array_diff($paths, ['..', '.', '.MyGitLight', 'added']);
		}
		$motherdir = dirname(__FILE__);
		if(!file_exists("$motherdir/added")){
			mkdir("$motherdir/added");
		}
		foreach ($paths as $origin){
            if (file_exists($origin)) {
                rec_copy($origin, "$motherdir/added/$origin");
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
					copy(dirname(__FILE__)."/man.txt", $path . "/.MyGitLight/man.txt");
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
<?php

class cache_files {
	private $cache_dir;
	private $current_call_cache;
	
	function __construct () {
		$this->cache_dir = ROOT_PATH.'views/cache/data/';
	}

    function flush() {
		$cache_dir = ROOT_PATH.'views/cache/data/';
        $result = false;
        $cached_files = glob($this->cache_dir.'*.cache');
        foreach ($cached_files as $val) {
			@unlink($this->cache_dir.$val);
		}
		$this->current_call_cache=false;
        return $result;
    }


    function get($key) {
        $file=$this->cache_dir.$key.'.cache';
        $data = false;
        if ( file_exists($file) && $f=fopen($file, 'r') && flock($f, LOCK_SH) ) {
			$raw_data = fread($f, filesize($file);
			if ($raw_data) {
				$data = json_decode($raw_data, true);
				if ( !empty($data['expires']) && $data['expire']>=$_SERVER['REQUEST_TIME'] ) {
					$data=$this->current_call_cache[$key]=$data['data'];
				}
			}
			flock($f, LOCK_UN);
		}
		if ($f) {
			@fclose($f);
		}
		return $data;
    }

    function set($key, $var, $compress=0, $expire=0) {
		$ret=false;
		if ( !empty($this->current_call_cache[$key]) ) {
			return $this->current_call_cache[$key];
		}
		$file=$this->cache_dir.$key.'.cache';
		$data = json_encode($var);
		$expire = $_SERVER['REQUEST_TIME'] + $expire;
		if ($f=fopen($file, 'w') && flock($f, LOCK_EX) ) {
			if ($f) {
				if ( @fwrite($f, Array('data'=>$data, 'expire'=>$expire)) !== false ){
					$ret = true;
					$this->current_call_cache[$key]=$data;
				}
			}
			flock($f, LOCK_UN);
		}
		if ($f) {
			@fclose($f);
		}
		return $ret;
    }


    function delete($key, $timeout=0) {
        $file=$this->cache_dir.$key.'.cache';
        unset($this->current_call_cache[$key]);
        @unlink($file);
    }
  
}

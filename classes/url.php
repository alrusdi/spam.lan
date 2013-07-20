<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class url {
    private static $default=Array('index');
    private static $error=Array('access', 'denied');
    const MAX_URL_SEGMENTS=10;
    const MAX_URL_SEGMENT_LENGTH=50;

    public static function parse($path){
        if (!$path){return self::$default;}
		$path=self::rewrite($path);
		$path=parse_url($path);
        $path=explode('/', self::clean($path['path']));

        if ( count($path)>self::MAX_URL_SEGMENTS ){return self::$error;}

        $ret=Array();

        foreach ($path as $val) {
            if (!$val){continue;}
            $ret[]=substr($val, 0, self::MAX_URL_SEGMENT_LENGTH);
        }
        if (!$ret) {$ret=self::$default;}
        return $ret;
    }
    
    
    public static function rewrite($path){
		$rules_file=ROOT_PATH.'/configs/url_rewrite.php';
		if (file_exists($rules_file) && $rules=include($rules_file)){
			foreach ($rules as $rule) {
				switch($rule['type']){
					case 'simple':
						$path=str_replace($rule['in'], $rule['out'], $path);
						break;
				}
			}
		}
		return $path;
	}

    public static function clean($path){
        $path=preg_replace('[^a-z0-9_\-\/\.\:]', '', $path);
        return preg_replace('|^'.BASE_URL.'|usi', '', $path);
    }
}

?>

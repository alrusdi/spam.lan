<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class log {
	public static $use_var_dump=false;
	
    public static function write(){
        $arg_list = func_get_args();
        ob_start();
        foreach ($arg_list as $val) {
			if (self::$use_var_dump) {
				var_dump($val);
			} else {
				print_r($val);
			}
		}
		$data=ob_get_contents();
		ob_end_clean();
		$fpath=ROOT_PATH.'/views/cache/logs/';
		$fname=date('Y_m_d_H').'.log';
		$f=fopen($fpath.$fname, 'a+');
		if ($f) {
			if (flock($f, LOCK_EX)) {
				$data="\n".'['.date('Y-m-d H:i:s').'] '.$data."\n";
				if (!fwrite($f, $data)) {
					echo 'cantwrite:'.$fpath.$fname;
				};
				flock($f, LOCK_UN);
			}
			@fclose($f);
		}
		
    }
}

?>

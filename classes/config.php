<?php
class config{
	public function get(){
		return SPAM_config;
	}
	
	public function get_statuses() {
		if (!SPAM_config::$STATUSES) {
			$data=file_get_contents(ROOT_PATH.'/configs/status.ini');
			if (!$data){return false;}
			$data=explode("\n", $data);
			$ret = Array();
			//print_r($data);
			foreach ($data as $rec) {
				$rec=trim($rec);
				
				if (!$rec || mb_substr($rec, 0, 1, 'utf-8')=='#') {
					continue;
				}
				
				$rec=explode('=', $rec);
				
				$key = trim($rec[0]);
				$val = trim($rec[1]);
				
				if ($key=='NATS_VERSION') {
					$cur_version = $val;
				} elseif ( $key=='CATEGORY'){
					$cur_category = $val;
				} else {
					$ret[$cur_version][$cur_category][$key]=$val;
				}
			}
			if ( !empty( $ret[ SPAM_config::$DB_NATS['version'] ] ) ) {
				SPAM_config::$STATUSES = $ret[ SPAM_config::$DB_NATS['version'] ];
			}
		}
		return SPAM_config::$STATUSES;
	}
}
?>

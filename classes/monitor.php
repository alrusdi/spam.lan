<?php

class monitor {
	public function get_info() {
		$info = FW::$core->caching->get('MAILING','MONITOR','INFO');
		$res = json_decode($info, true);
		if ( !empty($res['base']) &&  !empty($res['tasks']) ) {
			$cur_time = $_SERVER['REQUEST_TIME'];
			$sent = 0;
			
			foreach ($res['tasks'] as $key=>$val) {
				$sent+=intval($val['sent']/*+$val['fails']*/);
			}
			$time = $cur_time - intval($res['base']['started']);
			if ($time<60*60) {
				$p=$sent;
			} else {
				$p = number_format($sent/($time/(60*60)), 2);
			}
			$res['base']['productivity'] = $p;
		}
		return $res;
	}	
}


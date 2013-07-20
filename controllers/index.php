<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class index_controller extends base_controller {
	
	function __construct(){
		
	}
	
	public function index() {		
		$yday = FW::$core->tasks->get_by_day('YESTERDAY');
		$tday = FW::$core->tasks->get_by_day('TODAY');
		//$tmrow = FW::$core->tasks->get_by_day('TOMORROW');
		
		self::$tpl->assign('YESTERDAY', $yday);
		self::$tpl->assign('TODAY', $tday);
		//self::$tpl->assign('TOMORROW', $tmrow);
		
		if ($yday || $tday || $tmrow) {
			$templates=FW::$core->templates->get(Array(), Array('from'=>0, 'count'=>'All'));
			$users=FW::$core->users->get(Array(), Array('from'=>0, 'count'=>'All'));
			self::$tpl->assign('TEMPLATES', $templates['data']);
			self::$tpl->assign('USERS', $users);
		}

		$UNS_STATS =Array(
				'webmasters' => FW::$core->webmasters->get_subscriptions_stats(),
				'members' => FW::$core->members->get_subscriptions_stats()
			);
		self::$tpl->assign('UNS_STATS', $UNS_STATS);
		//print_r($UNS_STATS);
		self::$tpl->display('index.htm');
	}
	
	public function ajax_monitor() {
		$ret = FW::$core->monitor->get_info();
		if (!empty($ret['tasks']) ) {
			$task_ids = array_keys($ret['tasks']);
			$filters = Array(
				'by_id'=>$task_ids
			);
			$paging = Array(
				'count'=>'All',
				'from'=>0
			);
			$tasks = FW::$core->tasks->get($filters, $paging);
			self::$tpl->assign('TASKS', $tasks['data']);
		}
		self::$tpl->assign('DATA', $ret);
		self::$tpl->display('monitor.htm');
	}
}
?>

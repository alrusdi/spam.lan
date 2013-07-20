<?php
class tasks{
	private $DB=false;
	private $ERRORS=Array();
	function __construct(){
		$this->DB = FW::$core->db->spam->get_instance();
	}
	

	public function save($data) {
		if ($data=$this->validate($data)){
			$data['params_json']=json_encode($data['params']);
			unset($data['params']);			
			if ( empty($data['id']) ) {
				$data['creator_id']=$_SESSION['current_user']['id'];
				$data['creation_time']=date('Y-m-d H:i:s');
				$data['status']='NEW';
				$data['settings_nats_db_id']=SPAM_config::$DB_NATS['id'];
				$res=$this->DB->insert('tasks', $data);
			} else {
				$res=$this->DB->update('tasks', $data, 'id');
			}
			return $res;
		} else {
			return false;
		}
	}
	
	//$day in 'YESTERDAY', 'TODAY', 'TOMORROW'
	public function get_by_day($day = 'TODAY') {
		if ( !in_array($day, Array('YESTERDAY', 'TODAY', 'TOMORROW')) ) {return false;}
		switch ($day) {
			case 'YESTERDAY':
				$time = date('Y-m-d', strtotime('-1 day'));
				$time_from = $time.' 00:00:00';
				$time_to = $time.' 23:59:59';
				$rq = 'SELECT task_id FROM reports WHERE finished>=\''.$time_from.'\' AND finished<=\''.$time_to.'\'';
				$reps=$this->DB->query($rq);
				$task_ids=Array();
				foreach ($reps as $val) {
					if (!empty($val['task_id'])) {
						$task_ids[]=$val['task_id'];
					}
				}
				if ($task_ids) {
					$query='SELECT t.*, r.id AS report_id FROM tasks AS t, reports AS r WHERE t.id IN (\''.implode('\',\'',$task_ids).'\') AND t.id=r.task_id';
					return $this->DB->query($query);
				} else {
					return Array();
				}
				break;
			case 'TODAY':
				$time = date('Y-m-d');
				$time_from = $time.' 00:00:00';
				$time_to = $time.' 23:59:59';
				#retrieving complete tasks
				$rq = 'SELECT task_id FROM reports WHERE finished>=\''.$time_from.'\' AND finished<=\''.$time_to.'\''; 
				$reps=$this->DB->query($rq);
				$task_ids=Array();
				foreach ($reps as $val) {
					if (!empty($val['task_id'])) {
						$task_ids[]=$val['task_id'];
					}
				}
				$ctasks=Array();
				if ($task_ids) {
					$query='SELECT t.*, r.id AS report_id FROM tasks AS t, reports AS r WHERE t.id IN (\''.implode('\',\'',$task_ids).'\') AND t.id=r.task_id';
					$ctasks = $this->DB->query($query);
				}
				#retrieving new tasks
				$query='SELECT * FROM tasks WHERE 
						(status=\'NEW\' AND when_to_start>=\''.$time_from.'\' AND when_to_start<=\''.$time_to.'\' AND type=\'once\' AND is_disabled=0) 
						OR 
						(status IN(\'NEW\', \'CONTINUING\') AND DATEDIFF(\''.$time_to.'\', last_run_time)>=period AND when_to_start<=\''.$time_to.'\'  AND type=\'periodicaly\' AND is_disabled=0)';
				$newtasks = $this->DB->query($query);
				if (!$newtasks && !$ctasks) {
					return Array();
				} else {
					return(Array('NEW'=>$newtasks, 'COMPLETE'=>$ctasks));
				}						
				break;			
			case 'TOMORROW':
				$ttime=date('Y-m-d').' 00:00:00';
				$time = date('Y-m-d', strtotime('+1 day'));
				$time_from = $time.' 00:00:00';
				$time_to = $time.' 23:59:59';
				$query='SELECT * FROM tasks WHERE 
						(when_to_start>=\''.$time_from.'\' AND when_to_start<=\''.$time_to.'\' AND type=\'once\' AND is_disabled=0) 
						OR 
						( period<2 AND when_to_start<=\''.$time_to.'\'  AND type=\'periodicaly\' AND is_disabled=0)';
				$res=$this->DB->query($query);
				return $res;
				break;			
		}
	}
	
	public function get($filters, $paging_info=Array()){
		if (!$paging_info) {
			$paging_info=Array(
				'from'=>0,
				'count'=>SPAM_config::$paging['upperlimit']
			);
		}
		
		$where='';
		
		if ( !empty($filters['by_id']) ) {
			$ids=is_array($filters['by_id']) ? $filters['by_id'] : Array($filters['by_id']);
			foreach ($ids as $key=>$val) {
				$val=intval(trim($val));
				if ($val) {
					$ids[$key]=$val;
				} else {
					unset($ids[$key]);
				}
			}
			if ($ids) {
				$where.=' WHERE id IN ('.implode(',', $ids).')';
			}
		}
		
		$query_count='SELECT COUNT(*) as ct FROM tasks '.$where;
		$count=FW::$core->db->spam->query($query_count);

		if (!empty($count[0]['ct'])) {
			$count=$count[0]['ct'];
		} else {
			$count=0;
		}
		
		$data=Array();
		if ($count) {
			if ($paging_info['count']=='All') {$paging_info['count']=$count;}
			$query_data='SELECT * FROM tasks '.$where.' ORDER BY id DESC LIMIT '.$paging_info['from'].','.$paging_info['count'];
			$data=FW::$core->db->spam->query($query_data, 'id');
		}
		
		return Array('count'=>$count, 'data'=>$data);
	}
	
	public function get_errors(){
		return $this->ERRORS;
	}
	
	private function validate($data) {
		$errors=Array();
		$data['is_disabled'] = (!empty($data['is_disabled'])) ? 1 : 0;
		
		if ( empty($data['name']) ) {
			$errors[]='"Task name" - required';
		} elseif (!preg_match('/^[0-9a-zа-яё\ \_\-\!\;\"]{4,50}$/usi', $data['name'])) {
			$errors[]='Wrong task name - use 4-50 letters, digits, space and ,!-_;"';
		}
		
		if ( empty($data['when_to_start']) ) {
			$errors[]='"When to start" - required';
		} elseif ( !$this->is_date_valid($data['when_to_start']) ) {
			$errors[]='"When to start" - incorrect value (use popup, or type in YYYY-MM-DD format)';
		}
		
		if ( !in_array($data['type'], Array('once', 'periodicaly')) ) {
			$errors[]='"How to send mails" - required';
		} elseif ($data['type']=='periodicaly') {
			if ( empty($data['period']) ) {
				$errors[]='Period is required';
			} elseif( !preg_match('/^[0-9]{1,4}$/usi', $data['period']) ) {
				$errors[]='Period is wrong';
			}
		}

		if ( !empty($data['params']['uids_type']) ) {
			if ( in_array($data['params']['uids_type'], Array('members', 'webmasters')) ) {
				if ( !empty($data['params']['uids']) ) {
					$uids = implode(',', $data['params']['uids']);
					$uids = preg_replace('/[^0-9\,]/usi', '', $uids);
					$uids = explode(',', $uids);
					$data['params']['uids'] = $uids;
				} else {
					$errors[] = 'Application error (passed empty uids list)';
				}
			} else {
				$errors[] = 'Application error (passed wrong uids type)';
			}
			$data['params']['user_types']=$data['params']['member_statuses']='';
		} else {	
			if ( empty($data['params']['user_types']) || !in_array($data['params']['user_types'], Array('members', 'webmasters')) ) {
				$errors[]='"For which users" - required';
			} elseif ($data['params']['user_types'] == 'members') {
				if ( empty($data['params']['member_statuses']) ) {
					$errors[]='Choose at least one member status';
				} else {
					$sts=$data['params']['member_statuses'];
					foreach ($sts as $key => $val) {
						$val = preg_replace('/[^0-9]/usi', '', $val);
						$sts[$key] = $val;
					}
					$data['params']['member_statuses'] = $sts;
					unset($sts);
				}
			}
			$data['params']['uids_type']=$data['params']['uids']='';
		}
		$data['template_id'] = preg_replace('/[^0-9]/usi', '', $data['template_id']);
		if ( empty($data['template_id']) ) {
			$errors[] = '"Mail template to use" - required';
		}
		
	
		if ($errors) {
			$this->ERRORS = $errors;
			return false;
		}
		return $data;
	}
	
	private function is_date_valid($date) {
		$date=explode('-', $date);
		if (count($date)!=3){return false;}
		if ( $date[0] > date('Y') || $date[0]<2000) {
			return false;
		}
		$m=intval(ltrim($date[1], '0'));
		if ($data[1]>12 || $date[1]<1) {
			return false;
		}
		$d=intval(ltrim($date[2], '0'));
		if ($d>31 || $d<1) {
			return false;
		}
		return true;
	}
}
?>

<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class webmasters {  
	private $config=Array();
	private $DB = false;
	
	function __construct() {
		$this->config  = SPAM_config::$DB_NATS;
	}
	
	public function set_config($config){
		$this->config = $config;
		FW::$core->db->nats->get_instance($config);
	}
	
	public function unsubscribe($memberid) {
		if ($this->config['version']==4) {
			$res = FW::$core->db->nats->query('UPDATE login_detail SET mailok=0 WHERE loginid=\''.$memberid.'\'');
		} else {
			$res = FW::$core->db->nats->query('UPDATE accounts SET nomails=1 WHERE loginid=\''.$memberid.'\'');
		}
	}
	
	public function get_subscriptions_stats() {
		if ($this->config['version']==4) {
			return false;
		} else {
			$res = FW::$core->db->nats->query('SELECT COUNT(*) ct, nomails, "nomails" AS fieldname FROM accounts WHERE email<>\'\' GROUP BY nomails ORDER BY nomails');
			if (isset($res[0]['ct'])) {
				return $res;
			}
		}
		return false;
	}
	
    public function get($filters, $paging_info=Array()){
		if (!$this->config && !$this->DB) {
			$this->DB = FW::$core->db->nats->get_instance();
		}
		if (!$paging_info) {
			$paging_info=Array(
				'from'=>0,
				'count'=>SPAM_config::$paging['upperlimit']
			);
		}
		$table=($this->config['version']==3) ? 'accounts': 'login';
		$date_collumn = ($this->config['version']==3) ? 'ld.date_posted': 'l.join_date';
		$where='';
		
	
		if ( !empty($filters['by_date_from']) ) {
			$val=mb_substr(trim($filters['by_date_from']), 0, 10);
			$val=preg_replace('/[^0-9\-]/usi', '', $val);
			//if ($this->config['version']==4){
				$val=$this->convert_to_timestamp($val);
			//}
			$where.=' AND '.$date_collumn.'>=\''.$val.'\'';
		}	
		
		if ( !empty($filters['by_date_to']) ) {
			$val=mb_substr(trim($filters['by_date_to']), 0, 10);
			$val=preg_replace('/[^0-9\-]/usi', '', $val);
			//if ($this->config['version']==4){
				$val=$this->convert_to_timestamp($val);
			//}
			$where.=' AND '.$date_collumn.'<=\''.$val.'\'';
		}
		
		if (!empty($filters['by_email'])) {
			$val=trim($filters['by_email']);
			$val=mb_substr($val, 0, 100);
			if ($val) {
				$tbl_pfx = ($this->config['version']==3) ? 'l': 'ld';
				$where.=' AND '.$tbl_pfx.'.email LIKE \'%'.FW::$core->db->nats->escape($val).'%\'';
			}
		}
		
		if ( !empty($filters['by_username']) ) {
			$val=preg_replace('/[^a-z0-9]/usi', '', $filters['by_username']);
			$val=mb_substr($val, 0, 50);
			if ($val) {
				$where.=' AND l.username LIKE \'%'.FW::$core->db->nats->escape($val).'%\'';
			}			
		}
		
		if ( !empty($filters['by_id']) ) {
			$val='';
			foreach($filters['by_id'] as $uid) {
				$val.=intval(trim($uid)).',';
			}
			//removing last ','
			if (mb_substr($val, -1)==','){
				$val=mb_substr($val, 0, -1);
			}
			
			$where.=' AND l.loginid IN ('.$val.')';
		}
		
		if (isset($filters['by_status'])) {
			$val=trim($filters['by_status']);
			$val=mb_substr($val, 0, 50);
			$field_name = ($this->config['version']==3) ? 'active': 'status';
			$where.=' AND l.'.$field_name.'='.intval($val);
		}
		
		if (!empty($filters['by_neverlogin'])) {
			$table_prexix = ($this->config['version']==3) ? 'l': 'ld';
			$where.=' AND '.$table_prexix.'.last_login IS NULL';
		}	
		
		if ($this->config['version']==4) {
			$from_part = ' `login` AS l, `login_detail` AS ld WHERE l.deleted=0 AND l.loginid=ld.loginid ';
		} else {
			$from_part = ' `accounts` AS l, `account_details` AS ld WHERE l.loginid=ld.loginid ';
		}
			
		$query_count='SELECT COUNT(*) as ct FROM '.$from_part.' '.$where;
		#print($query_count);
		$count=FW::$core->db->nats->query($query_count);
		if (!empty($count[0]['ct'])) {
			$count=$count[0]['ct'];
		} else {
			$count=0;
		}
		//SELECT l.loginid AS memberid, l.username, l.status, l.join_date, CONCAT(ld.firstname, '', ld.lastname) AS `fullname`, ld.email AS `email` FROM `login` AS l,  `login_detail` AS ld WHERE l.loginid=ld.loginid
		$data=Array();
		if ($count) {
			if ($paging_info['count']=='All') {$paging_info['count']=$count;}
			if ($this->config['version']==4) {
				$query_data='SELECT l.loginid AS memberid, l.username, l.status, l.join_date AS joined, ld.mailok AS wants_to_recieve_mails, CONCAT(ld.firstname, \' \', ld.lastname) AS `fullname`, ld.email AS `email`
								FROM ';
			} else {
				$query_data='SELECT l.loginid AS memberid, l.username, l.active AS status, NOT(l.nomails) AS wants_to_recieve_mails, FROM_UNIXTIME(ld.date_posted) AS joined, CONCAT(l.firstname, \' \', l.lastname) AS `fullname`, l.email AS `email` 
								FROM ';
			}
			$query_data.=$from_part.' '.$where.' ORDER BY l.username LIMIT '.$paging_info['from'].','.$paging_info['count'];
			//die($query_data);
			$data=FW::$core->db->nats->query($query_data);
		}
		
		return Array('count'=>$count, 'data'=>$data);
	}
	
	public function get_statuses() {
		if ($this->config['version']==4) {
			$possible_statuses = Array('0' => 'active', '1' => 'disabled', '2'=>'unknown(2)', '3'=>'banned');
			$query='SELECT DISTINCT(status) AS status FROM login WHERE deleted=0';
		} else {
			$possible_statuses = Array('0' => 'disabled', '1' => 'active');
			$query='SELECT DISTINCT(active) AS status FROM accounts';
		}
		$data=FW::$core->db->nats->query($query, 'status');
		return array_intersect_key($possible_statuses, $data);
	}
	
	private function convert_to_timestamp($date_str){
			$date=explode('-', $date_str);
			return mktime(0,0,0,$date[1], $date[2], $date[0]);
	}
}

?>

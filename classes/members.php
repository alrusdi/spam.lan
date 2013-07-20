<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class members {
	private $config=Array();
	private $DB = false;
	
	function __construct() {
		$this->config  = SPAM_config::$DB_NATS;
	}
	
	public function unsubscribe($memberid) {
		if ($this->config['version']==4) {
			$res = FW::$core->db->nats->query('UPDATE member SET mailok=2 WHERE memberid=\''.$memberid.'\'');
		} else {
			$res = FW::$core->db->nats->query('UPDATE members SET mailok=2 WHERE memberid=\''.$memberid.'\'');
		}
	}
	
	public function get_subscriptions_stats() {
		if ($this->config['version']==4) {
			return false;
		} else {
			$res = FW::$core->db->nats->query('SELECT COUNT(*) AS ct, mailok, "mailok" AS fieldname FROM members WHERE email<>\'\' GROUP BY mailok ORDER BY mailok');
			if (isset($res[0]['ct'])) {
				return $res;
			}
		}
		return false;
	}
	
	public function set_config($config){
		$this->config = $config;
		FW::$core->db->nats->get_instance($config);
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
		
		$table=($this->config['version']==3) ? 'members': 'member';
		$where='';
		
		if ( !empty($filters['by_username']) ) {
			$val=preg_replace('/[^a-z0-9_ ]/usi', '', $filters['by_username']);
			$val=mb_substr($val, 0, 50);
			if ($val) {
				$where.=' AND username LIKE \'%'.FW::$core->db->nats->escape($val).'%\'';
			}			
		}
		
		if (isset($filters['by_status'])) {
			$val=trim($filters['by_status']);
			$val=mb_substr($val, 0, 50);
			$where.=' AND status='.$val;
		}
		
		if (!empty($filters['by_email'])) {
			$val=trim($filters['by_email']);
			$val=mb_substr($val, 0, 100);
			if ($val) {
				$where.=' AND email LIKE \'%'.FW::$core->db->nats->escape($val).'%\'';
			}
		}
		
		if ( !empty($filters['by_date_from']) ) {
			$val=mb_substr(trim($filters['by_date_from']), 0, 10);
			$val=preg_replace('/[^0-9\-]/usi', '', $val);
			if ($this->config['version']==4){
				$val=$this->convert_to_timestamp($val);
			}
			$where.=' AND joined>=\''.$val.'\'';
		}	
		
		if ( !empty($filters['by_date_to']) ) {
			$val=mb_substr(trim($filters['by_date_to']), 0, 10);
			$val=preg_replace('/[^0-9\-]/usi', '', $val);
			if ($this->config['version']==4){
				$val=$this->convert_to_timestamp($val);
			}
			$where.=' AND joined<=\''.$val.'\'';
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
			$where.=' AND memberid IN ('.$val.')';
						
		}
		
		if ($where) {
			$where=' WHERE'.$where;
			$where=str_replace('WHERE AND', 'WHERE', $where);
		}		
		
		$query_count='SELECT COUNT(*) as ct FROM `'.$table.'` '.$where;
		//die($query_count);
		$count=FW::$core->db->nats->query($query_count);
		if (!empty($count[0]['ct'])) {
			$count=$count[0]['ct'];
		} else {
			$count=0;
		}
		$data=Array();
		if ($count) {
			if ($paging_info['count']=='All') {$paging_info['count']=$count;}
			$query_data='SELECT * FROM `'.$table.'` '.$where.' ORDER BY username LIMIT '.$paging_info['from'].','.$paging_info['count'];
			//die($query_data);
			$data=FW::$core->db->nats->query($query_data);
			/*
			$disabled_emails = FW::$core->mailer->get_disabled_emails();
			if ($disabled_emails) {
				foreach ($data as $key=>$val) {
					if (in_array($val['email'], $disabled_emails) ) {
						$data[$key]['wants_to_recieve_emails'] = false;
					}
				}
			}
			*/
		}
		return Array('count'=>$count, 'data'=>$data);
	}
	
	public function get_statuses() {
		return Array('0' => 'never joined', '1' => 'active', '2' => 'expired');
	}
	
	private function convert_to_timestamp($date_str){
			$date=explode('-', $date_str);
			return mktime(0,0,0,$date[1], $date[2], $date[0]);
	}
}

?>

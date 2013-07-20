<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class members_controller extends base_controller {
	private $pway=Array(
						Array('name'=>'home', 'path'=>''),
						Array('name'=>'members', 'path'=>'members/')
					);
					
	function __construct() {
		$this->config  = SPAM_config::$DB_NATS;
	}
		
	public function index() {
		$cur_page=(!empty($_GET['page'])) ? intval(trim($_GET['page'])): 1;
		self::$tpl->assign('current_page', $cur_page);
		
		
		//RPP - number of Records Per Page
		$RPP_list=Array(10,50,100,'All');
		$current_RPP=( !empty($_GET['rpp']) && in_array($_GET['rpp'], $RPP_list) ) ? $_GET['rpp']: SPAM_config::$paging['upperlimit'];
		self::$tpl->assign('RPP_list', $RPP_list);
		self::$tpl->assign('current_RPP', $current_RPP);

		$filters=$this->get_filters();
		$data=FW::$core->members->get(
										$filters, 
										Array(
											'from'=>($cur_page-1)*$current_RPP, 
											'count'=>$current_RPP,
											)
										);
		
		self::$tpl->assign('data', $data);
		
		if ($data['count']) {
			self::$tpl->assign('URL', '&rpp='.$current_RPP.$this->filters2request($filters));
			$paging_settings=Array(
				'current_page'=>$cur_page,
				'total_records'=>$data['count'],
				'records_per_page'=>$current_RPP,
				'max_page_numbers_per_page'=>SPAM_config::$paging['max_page_numbers_per_page']			
			);
			self::$tpl->assign('PAGING', FW::$core->paging->settings($paging_settings)->get_assoc_list());
		}
		
		self::$tpl->assign('STATUSES', FW::$core->members->get_statuses());
		self::$tpl->assign('NATS_VERSION', SPAM_config::$DB_NATS['version']);
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->display('members.htm');
	}
	
	private function get_filters() {
		$filters=Array();
		foreach ($_GET as $key=>$val) {
			if (substr($key, 0,3)=='by_') {
				$filters[$key]=substr(trim($val), 0, 20);
				if ($key=='by_id') {
					$filters[$key] = Array(preg_replace('/[^0-9]/usi', '', $filters[$key]));
				}
			}
		}
		return $filters;		
	}
	
	private function filters2request($filters){
		$ret='';
		if ($filters){
			foreach ($filters as $key=>$val){
				$ret.='&'.$key.'='.$val;
			}
		}
		return $ret;
	}
}
?>

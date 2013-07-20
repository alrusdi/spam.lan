<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class tasks_controller extends base_controller {
	private $pway=Array(
					Array('name'=>'home', 'path'=>''),
					Array('name'=>'tasks', 'path'=>'tasks/')
				);
				
	public function index() {
		$cur_page=(!empty($_GET['page'])) ? intval(trim($_GET['page'])): 1;
		self::$tpl->assign('current_page', $cur_page);
		
		
		//RPP - number of Records Per Page
		$RPP_list=Array(10,50,100,'All');
		$current_RPP=( !empty($_GET['rpp']) && in_array($_GET['rpp'], $RPP_list) ) ? $_GET['rpp']: SPAM_config::$paging['upperlimit'];
		self::$tpl->assign('RPP_list', $RPP_list);
		self::$tpl->assign('current_RPP', $current_RPP);

		$filters=$this->get_filters();
		$data=FW::$core->tasks->get(
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
			$templates=FW::$core->templates->get(Array(), Array('from'=>0, 'count'=>'All'));
			$users=FW::$core->users->get(Array(), Array('from'=>0, 'count'=>'All'));
			self::$tpl->assign('TEMPLATES', $templates['data']);
			self::$tpl->assign('USERS', $users);
		}
		
		self::$tpl->assign('NATS_VERSION', SPAM_config::$DB_NATS['version']);
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->display('tasks/list.htm');
	}
		
	public function edit($id=false) {
		//print_r($_POST);
		if ( !empty($_POST['uids']) && !empty($_POST['uids_type']) ) {
			$this->set_users_info($_POST['uids_type'], json_decode($_POST['uids']));
		} elseif (!empty($_POST)) {
			$data=Array();
			$id=intval(trim($this->postval('id')));
			if ($id) {
				$data['id']=$id;
			}
			$data['name']=$this->postval('name');
			$data['type']=$this->postval('type');
			$data['template_id']=$this->postval('template_id');
			$data['period']=$this->postval('period', 0);
			$data['when_to_start']=$this->postval('when_to_start', '');
			$data['settings_nats_db_id']=$this->postval('settings_nats_db_id', SPAM_config::$DB_NATS['id']);
			
			$data['is_disabled']=$this->postval('is_disabled', '0');
			$data['params']['user_types']=$this->postval(Array('params', 'user_types'));
			if ($data['params']['user_types']=='members') {
				$data['params']['member_statuses']=$this->postval(Array('params', 'member_statuses'));
			}

			$data['params']['uids_type']=$this->postval(Array('params', 'uids_type'), '');
			$data['params']['uids']=$this->postval(Array('params', 'uids'), '');
			if ($data['params']['uids']) {
				$this->set_users_info($data['params']['uids_type'], $data['params']['uids'], $data['settings_nats_db_id']);
			}
			$res = FW::$core->tasks->save($data);
			if ($res) {
				$message = 'Task saved';
				self::$tpl->assign('message', $message);
				self::$tpl->display('message.htm');
				exit();
			} else {
				$errors = FW::$core->tasks->get_errors();
				self::$tpl->assign('errors', $errors);
			}
			self::$tpl->assign('data', $data);
		}
		
		$templates=FW::$core->templates->get(Array('is_disabled'=>0), Array('from'=>0, 'count'=>'All'));
		self::$tpl->assign('TEMPLATES', $templates['data']);
		
		if ($id && empty($data)) {
			$data=FW::$core->tasks->get(
										Array('by_id'=>$id), //filter
										Array('from'=>0, 'count'=>1) //paging
										);
			if (!empty($data['data'][0])) {
				$data=$data['data'][0];
				$data['params']=json_decode($data['params_json'], true);
				if ($data['params']['uids']) {
					$this->set_users_info($data['params']['uids_type'], $data['params']['uids'], $data['settings_nats_db_id']);
				} 
				self::$tpl->assign('data', $data);
			}
		}
		
		$this->pway[] = Array('name'=>'edit', 'path'=>'tasks/edit/');
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
				
		self::$tpl->assign('STATUSES', FW::$core->members->get_statuses());
		self::$tpl->display('tasks/edit.htm');
	}
	
	private function postval($key, $default=false) {
		if (is_array($key)) {
			return (!empty($_POST[$key[0]][$key[1]])) ? $_POST[$key[0]][$key[1]] : $default;
		} else {
			//echo $key.'=>'.$_POST[$key].'<br />';
			return (!empty($_POST[$key])) ? $_POST[$key] : $default;
		}
	}
	
	private function set_users_info($uids_type, $uids=Array(), $settings_version=false) {
		if (!in_array($uids_type, Array('members', 'webmasters'))){return false;}
		if ( $setting_version && $setting_version!=SPAM_config::$DB_NATS['id'] ) {
			$all_settings = FW::$core->settings->get(false);
			if ( !empty($all_settings[$setting_version]) ) {
				FW::$core->$uids_type->set_config($all_settings[$setting_version]);
			} else {
				echo 'Can\'t find DB settings for this task';
				die();
			}
		}
		$users=FW::$core->$uids_type->get(
			Array( 'by_id'=>$uids ),
			Array(
				'from'=>0,
				'count'=>'All'
			)
		);
		self::$tpl->assign('USERS_SET_TYPE', $uids_type);
		self::$tpl->assign('USERS_SET', $users);	
	}
	
	private function get_filters() {
		$filters=Array();
		foreach ($_GET as $key=>$val) {
			if (substr($key, 0,3)=='by_') {
				$filters[$key]=substr(trim($val), 0, 20);
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

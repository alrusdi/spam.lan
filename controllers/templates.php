<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class templates_controller extends base_controller {
	private $pway=Array(
						Array('name'=>'home', 'path'=>''),
						Array('name'=>'templates', 'path'=>'templates/')
					);
		
	public function index() {
		$cur_page=(!empty($_GET['page'])) ? intval(trim($_GET['page'])): 1;
		self::$tpl->assign('current_page', $cur_page);
		
		
		//RPP - number of Records Per Page
		$RPP_list=Array(10,50,100,'All');
		$current_RPP=( !empty($_GET['rpp']) && in_array($_GET['rpp'], $RPP_list) ) ? $_GET['rpp']: SPAM_config::$paging['upperlimit'];
		self::$tpl->assign('RPP_list', $RPP_list);
		self::$tpl->assign('current_RPP', $current_RPP);

		$data=FW::$core->templates->get(
										Array(), 
										Array(
											'from'=>($cur_page-1)*$current_RPP, 
											'count'=>$current_RPP,
											)
										);
		
		self::$tpl->assign('data', $data);
		
		if ($data['count']) {
			self::$tpl->assign('URL', '&rpp='.$current_RPP);
			$paging_settings=Array(
				'current_page'=>$cur_page,
				'total_records'=>$data['count'],
				'records_per_page'=>$current_RPP,
				'max_page_numbers_per_page'=>SPAM_config::$paging['max_page_numbers_per_page']			
			);
			self::$tpl->assign('PAGING', FW::$core->paging->settings($paging_settings)->get_assoc_list());
		}
		
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->display('templates/index.htm');
	}
	
	public function edit($id=false, $mode=false) {
		
		//saving (ajax request)
		if (!empty($_POST)){
			$ret=Array('status'=>'error', 'message'=>'');
			if (FW::$core->templates->save($_POST)){
				$ret=Array('status'=>'ok', 'message'=>'');
			}
			echo json_encode($ret);
			exit();
		}
		
		
		
		//appending pathway
		$this->pway[]=Array('name'=>'edit', 'path'=>'templates/edit/');
		self::$tpl->assign('PATHWAY', $this->pway);	
		
		$data=Array();
			
		//displaying succes page
		if ($id=='ok'){
			self::$tpl->assign('message', 'Template saved successfully');
			self::$tpl->display('message.htm');
			exit();
		} else {
			$data=FW::$core->templates->get_by_id($id);
		}
		
		self::$tpl->assign('data', $data);
		self::$tpl->assign('NATS_VERSION', SPAM_config::$DB_NATS['version']);
		self::$tpl->display('templates/edit.htm');
	}
	
	public function delete($id=0){
		$ret=Array('status'=>'error', 'message'=>'');
		if (FW::$core->templates->delete($id)){
			$ret=Array('status'=>'ok', 'message'=>'');
		}
		echo json_encode($ret);
	}

	private function get_filters() {
		return Array();
	}
}
?>

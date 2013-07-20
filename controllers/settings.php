<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class settings_controller extends base_controller {
	private $pway=Array(
					Array('name'=>'home', 'path'=>''),
					Array('name'=>'settings', 'path'=>'settings/')
				);
				
	public function index(){
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->display('settings/index.htm');
	}
	
	public function mailing() {
		if (!empty($_POST)) {
			$data=$_POST;
			
			if ( !empty($data['servers']['server']) && count($data['servers']['server'])==1 && $this->is_all_fields_are_empty($data['servers']) ) {
				unset($data['servers']);
			}
			if ( !empty($data['servers']) ) {
				$servers=Array();
				foreach ($data['servers'] as $key=>$vals) {
					foreach ($vals as $index=>$val) {
						$servers[$index][$key]=trim($val);
					}
				}
				$data['servers']=$servers;
			}
				
			$res=FW::$core->settings->save_mailing('email_settings', $data);

			if ($res) {
				$message = 'Settings are saved';
				$state = true;
			} else {
				$message = 'Error while saving settings';
				$state = false;
			}
			self::$tpl->assign('STATE', $state);
			self::$tpl->assign('MESSAGE', $message);
		}
		
		$data=FW::$core->settings->get(false);
		$this->pway[]=Array('name'=>'mailing', 'path'=>'settings/mailing/');
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->assign('data', $data['email_settings']);
		self::$tpl->display('settings/mailing.htm');
	}
	
	public function db() {
		if (!empty($_POST['data'])) {
			$res=FW::$core->settings->save_db('settings_db_nats', $_POST['data']);
			
			if ($res) {
				$message = 'Settings are saved';
				$state = true;
			} else {
				$message = 'Error while saving settings';
				$state = false;
			}
			self::$tpl->assign('STATE', $state);
			self::$tpl->assign('MESSAGE', $message);
		}
		$data=FW::$core->settings->get(false);
		$this->pway[]=Array('name'=>'DB', 'path'=>'settings/db/');
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		
		self::$tpl->assign('data', $data['nats_db_settings']);		
		self::$tpl->display('settings/nats_db.htm');
	}
	
	private function is_all_fields_are_empty($data) {
		foreach ($data as $val) {
			if (!empty($val[0]) && trim($val[0])){return false;}
		}
		return true;
	}

}
?>

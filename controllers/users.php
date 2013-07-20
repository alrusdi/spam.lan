<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class users_controller extends base_controller {
	private $pway=Array(
					Array('name'=>'home', 'path'=>''),
					Array('name'=>'settings', 'path'=>'settings/'),
					Array('name'=>'users', 'path'=>'users/')
				);
	
	public function index(){
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->assign('data', FW::$core->users->get());
		self::$tpl->display('users/list.htm');
	}
		
	public function edit($id=false) {
		//print_r($_POST);
		if ($id=='ok'){
			self::$tpl->assign('message', 'User\'s data saved successfully');
			self::$tpl->display('message.htm');
			exit();
		}
		$roles=Array('administrator', 'manager');
		self::$tpl->assign('ROLES', $roles);
		if (!empty($_POST)) {
			$data=Array();
			$id=intval(trim($this->postval('id')));
			if ($id) {$data['id']=$id;}
			$data['name']=$this->postval('name');
			$data['comment']=strip_tags($this->postval('comment'));
			$data['role']=$this->postval('role');
			$data['passphrase']=$this->postval('passphrase');
			$data['email']=$this->postval('email');
			$data['is_blocked']=$this->postval('is_blocked', '0');
			$res=FW::$core->users->save($data);
			if ($res) {
				header('Location: '.BASE_URL.'users/edit/ok/');
			} else {
				self::$tpl->assign('errors', implode('<br />', FW::$core->users->get_errors()));
			}
			self::$tpl->assign('data', $data);
		}
		
		if ($id && empty($data)) {
			$data=FW::$core->users->get(
										Array('by_id'=>$id), //filter
										Array('from'=>0, 'count'=>1) //paging
										);
			
			if (!empty($data)) {
				$data=current($data);
				self::$tpl->assign('data', $data);
			}
			
			if ( $_SESSION['current_user']['role']!='administrator' && $data['id']!=$_SESSION['current_user']['id'] ) {
				self::$tpl->assign('message', 'You can\'t manage this user');
				self::$tpl->display('message.htm');
				exit();				
			}
		}
		$this->pway[] = Array('name'=>'edit', 'path'=>'users/edit/');
		self::$tpl->assign('PATHWAY', $pway=$this->pway);
		self::$tpl->display('users/edit.htm');
	}
	
	public function del($id=false){
		$id=intval(trim($id));
		if (!$id || $id==$_SESSION['current_user']['id'] || $_SESSION['current_user']['role']!='administrator') {
			self::$tpl->assign('message', 'You can\'t delete this user');
		} else {
			$res=FW::$core->users->delete($id);
			self::$tpl->assign('message', ($res) ? 'User sucessfully deleted' : 'Error! Can\'t delete user from database');
		}
		self::$tpl->display('message.htm');
	}
	
	private function postval($key, $default=false, $possible_values=Array()) {
		if (is_array($key)) {
			return (!empty($_POST[$key[0]][$key[1]])) ? $_POST[$key[0]][$key[1]] : $default;
		} else {
			return (!empty($_POST[$key])) ? $_POST[$key] : $default;
		}
	}
	
	private function set_users_info($uids_type, $uids=Array()) {
		echo $uids_type;
		if (!in_array($uids_type, Array('members', 'webmasters'))){return false;}
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

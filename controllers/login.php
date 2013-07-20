<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class login_controller extends base_controller {

	public function index() {
		if (isset($_POST['passphrase'])){
			$user=FW::$core->users->check_passphrase($_POST['passphrase']);
			if (!empty($user[0]['id'])){
				$_SESSION['current_user']=$user[0];
				header('Location: '.BASE_URL);
				exit();	
			} else {
				self::$tpl->assign('msg', 'Wrong passphrase!');
			}
		}
		
		FW::$core->log;
		log::write('Tries to login:'.$_SERVER['REMOTE_ADDR']);
		self::$tpl->display('login.htm');
	}
	
	public function logout() {
		session_destroy();
		header('Location: '.BASE_URL.'login/');		
	}
	
}
?>

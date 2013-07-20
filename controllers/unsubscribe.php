<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class unsubscribe_controller extends base_controller {

	public function index($hash='') {
		$hash = preg_replace('/[^a-fmw0-9\-]/usi', '', $hash);
		$hash_arr = explode('-', $hash);
		
		$result=false;
		
		if ( $hash && count($hash_arr)==2 ) {
			$memberid = preg_replace('/[^0-9]/usi', '', $hash_arr[0]);
			
			$settings = FW::$core->settings->get(true);
			$secret_phrase = ( isset($settings['email_settings'][0]['secret_phrase']) ) ? $settings['email_settings'][0]['secret_phrase'] : false;
			$link_check = md5($memberid.$secret_phrase);
			
			if ($link_check == $hash_arr[1]) {
				$filters = Array('by_id'=>Array($memberid));
				if ( mb_substr($hash, 0, 1)=='w' ) {
					$res = FW::$core->webmasters->unsubscribe($memberid);
					$user = FW::$core->webmasters->get($filters);
				} else {
					$res = FW::$core->members->unsubscribe($memberid);
					$user = FW::$core->members->get($filters);
				}

				if ( !empty($user['data'][0]['email']) ) {
					//FW::$core->mailer->disable_email($user['data'][0]['email']);
					$result=true;
				}
			}
		}
		
		if (!$result) {
			FW::$core->log;
			log::write('Unsubscribe failed:'.$hash);
		}

		self::$tpl->assign('RESULT', $result);
		self::$tpl->display('unsubscribe_result.htm');
	}
	
}
?>

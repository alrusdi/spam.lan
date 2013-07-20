<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class mailer {
	
	private $DB;
	
	function __construct(){
		$this->DB = FW::$core->db->spam->get_instance();
	}
		
	public function disable_email($email){
		$email = $this->DB->escape($email);
		$this->DB->query('INSERT INTO disabled_emails (`email`) VALUES (\''.$email.'\') ON DUPLICATE KEY UPDATE `email`=\''.$email.'\'');
	}
	
	public function get_disabled_emails() {
		return array_keys($this->DB->query('SELECT * FROM disabled_emails', 'email'));
	}
	
}

?>

<?php
class settings{
	private $DB=false;
	private $ERRORS=Array();
	function __construct(){
		$this->DB = FW::$core->db->spam->get_instance();
	}
	

	public function save_db($table, $data) {
		if (!empty($data['settings_to_delete'])) {
			$dtd = preg_replace('/[^0-9,]/usi', '' , $data['settings_to_delete']);
			$this->DB->query('DELETE FROM `'.$table.'` WHERE id IN ('.$dtd.')');
			unset($data['settings_to_delete']);
		}
		foreach ($data as $val) {
			$this->DB->insert_or_update($table, $val);
		}
		return true;
	}
	
	public function save_mailing($table, $data) {	
		if ( empty($data['settings_email_id']) || !preg_replace('/[^0-9]/usi', '', $data['settings_email_id']) ) {
			return false;
		}
			
		if ( !empty($data['servers_to_delete']) ) {
			$dtd = preg_replace('/[^0-9,]/usi', '' , $data['servers_to_delete']);
			$this->DB->query('DELETE FROM `settings_email_servers` WHERE id IN ('.$dtd.')');
			unset($data['servers_to_delete']);
		}
			
		
		if ( isset($data['timeout']) && isset($data['base_url']) ) {
			$timeout = preg_replace('/[^0-9]/usi', '', $data['timeout']);
			$base_url = preg_replace('/[^a-z0-9\:\/\-\.]/usi', '', $data['base_url']);
			if ($base_url) {
				if ( mb_substr($base_url, 0, 7)!='http://' ) {
					$base_url='http://'.$base_url;
				}
				if ( mb_substr($base_url, -1)!='/' ) {
					$base_url.='/';
				}
			}
			
			$secret_phrase = preg_replace('/[^a-zA-Z0-9]/usi', '', $data['secret_phrase']);
			$this->DB->query('UPDATE `settings_email` SET timeout='.$timeout.', base_url=\''.$this->DB->escape($base_url).'\', secret_phrase=\''.$this->DB->escape($secret_phrase).'\'');
		} else {
			return false;
		}
		
		if ( !empty($data['servers']) ) {
			foreach ($data['servers'] as $val) {
				$val['settings_email_id'] = $data['settings_email_id'];
				$this->DB->insert_or_update('settings_email_servers', $val);
			}
		}
		return true;
	}	
	
	public function get($active=true){
		$ret=Array();
		
		$email_data=$this->DB->query('SELECT e.timeout, e.base_url, e.secret_phrase, es.* FROM settings_email AS e, settings_email_servers AS es WHERE es.settings_email_id=e.id');		
		if ( !empty($email_data[0]['id']) ) {
			$ret['email_settings']=$email_data;
		}
		
		$q='SELECT * FROM settings_db_nats';
		if ($active) {
			$q.=' WHERE is_active=1'; 
		}
		
		$nats_data=$this->DB->query($q, 'id');		
		if ( count($nats_data>0) ) {
			$ret['nats_db_settings']=$nats_data;
		}
		return $ret;
	}
	
	public function get_errors(){
		return $this->ERRORS;
	}
	
	private function validate($data) {
		return true;
	}
}
?>

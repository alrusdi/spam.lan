<?php
class users{
	private $DB=false;
	private $errors=Array();
	
	function __construct(){
		$this->DB = FW::$core->db->spam->get_instance();
	}
	
	public function check_passphrase($passphrase) {
		$passphrase=substr($passphrase, 0, 100);
		$passphrase=md5($passphrase);
		$res=$this->DB->query('SELECT * FROM users WHERE passphrase=\''.$passphrase.'\' AND is_blocked=0 AND is_removed=0 LIMIT 1');
		return $res;
	}
	

	public function save($data) {
		if (!$this->validate($data)){return false;}
		if ( !$data['passphrase'] ) {
			unset($data['passphrase']);
		} else {
			$data['passphrase']=md5($data['passphrase']);
		}
		
		$role_admin=($_SESSION['current_user']['role']=='administrator');
		if (!empty($data['id'])) {
			if ( !$role_admin && $data['role']!='manager' ) {
				$this->errors[]='Only administrators can change user\' role to this value ('.$data['role'].')';
				return false;
			}
			return $this->DB->update('users', $data, 'id');
		} else {
			if (!$role_admin) {
				$this->errors[]='Only administrators can create new system users';
				return false;
			}
			$data['added_time']=date('Y-m-d H:i:s');
			return $this->DB->insert('users', $data);
		}
	}
	
	private function validate($data){
		return true;
	}
	
	public function get_errors(){
		return $this->errors;
	}
	
	public function get($filters=Array(), $paging_info=Array()) {
		$where='';
		if ( !empty($filters['by_id']) ) {
			$val='';
			if (!is_array($filters['by_id'])) {$filters['by_id']=Array($filters['by_id']);}
			foreach($filters['by_id'] as $uid) {
				$val.=intval(trim($uid)).',';
			}
			//removing last ','
			if (mb_substr($val, -1)==','){
				$val=mb_substr($val, 0, -1);
			}
			$where.=' AND id IN ('.$val.')';
						
		}
		$query='SELECT * FROM users WHERE is_removed=0 '.$where.' ORDER BY name';
		//die($query);
		return $this->DB->query($query, 'id');
	}
	
	public function delete($id){
		$id=intval(trim($id));
		$ret=false;
		if ($id) {
			$ret=$this->DB0->query('DELETE FROM users WHERE id=\''.$id.'\' LIMIT 1');
		}
		return $ret;
	}
}
?>

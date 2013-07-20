<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class templates {  
    public function get($filters, $paging_info=Array()){
		if (!$paging_info) {
			$paging_info=Array(
				'from'=>0,
				'count'=>SPAM_config::$paging['upperlimit']
			);
		}
		
		$where='';
		if ( isset($filters['is_disabled']) ) {
			$where.=' WHERE is_disabled='.intval(trim($filters['is_disabled']));
		}
		$query_count='SELECT COUNT(*) as ct FROM templates '.$where;
		$count=FW::$core->db->spam->query($query_count, 'id');
		$count = (empty($count[0]['ct'])) ? 0: $count[0]['ct'];
	
		$data=Array();
		if ($count) {
			if ($paging_info['count']=='All') {$paging_info['count']=$count;}
			$query_data='SELECT * FROM templates '.$where.' ORDER BY id DESC LIMIT '.$paging_info['from'].','.$paging_info['count'];
			$data=FW::$core->db->spam->query($query_data, 'id');
		}
		
		return Array('count'=>$count, 'data'=>$data);
	}
	
	public function get_by_id($id) {
		$id=intval(trim($id));
		if (!$id){ return false; }
		$data=FW::$core->db->spam->query('SELECT * FROM templates WHERE id=\''.$id.'\'');
		$ret=false;
		if ( !empty($data[0]['id']) ) {
			$ret=$data[0];
		}
		return $ret;
	}
	
	public function save($data) {
		if (!$data=$this->validate($data)){
			return false;
		}
		if (empty($data['id'])) {
			$data['creator_id']=$_SESSION['current_user']['id'];
			$data['creating_time']=date('Y-m-d H:i:s');
			return FW::$core->db->spam->insert('templates', $data);
		} else {
			return FW::$core->db->spam->update('templates', $data, 'id');
		}
	}
	
	public function delete($id){
		if (!$id){return false;}
		$id=intval(trim($id));
		//checking if linked tasks exsits
		$tasks_nr=FW::$core->db->spam->query('SELECT COUNT(*) as ct FROM tasks WHERE template_id=\''.$id.'\'');
		if ( !empty($tasks_nr[0]['ct']) ) {return false;}
		return FW::$core->db->spam->query('DELETE FROM templates WHERE id=\''.$id.'\'');
	}
	
	private function validate($data){
		$ret=Array();
		if (!empty($data['id'])) {
			$data['id']=intval(trim($data['id']));
			if (!$data['id']){ return false; }
			$ret['id']=$data['id'];
		}
		
		if (empty($data['name']) || !preg_match('/^[a-z0-9\ \%\,\.\-\!\[\]]{1,255}$/usi', $data['name'])){
			return false;
		}
		
		if (empty($data['email_from']) || !preg_match('/^([a-z0-9_\.\-])+@(([a-z0-9\-])+.)+([a-z0-9]{2,4})+$/usi', $data['email_from'])){
			return false;
		}
		
		$ret['sender_name'] = '';
		if ( !empty($data['sender_name']) ) {
			$ret['sender_name']= mb_substr($data['sender_name'], 0, 100);
		}
		
		$ln=mb_strlen($data['contents'], 'UTF8');
		if ($ln<1 || $ln>50000){
			return false;
		}
		$ret['name']=$data['name'];
		$ret['contents']=$data['contents'];
		$ret['email_from']=mb_substr($data['email_from'], 0, 100);
		$ret['is_disabled']=(!empty($data['is_disabled'])) ? 1: 0;
		return $ret;
	}
}

?>

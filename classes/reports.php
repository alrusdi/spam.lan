<?php
class reports{
	private $DB=false;
	private $ERRORS=Array();
	function __construct(){
		$this->DB = FW::$core->db->spam->get_instance();
	}
	

	public function get($filters, $paging_info=Array()){
		if (!$paging_info) {
			$paging_info=Array(
				'from'=>0,
				'count'=>SPAM_config::$paging['upperlimit']
			);
		}
		
		$where='';
		
		if ( !empty($filters['by_id']) ) {
			$ids=is_array($filters['by_id']) ? $filters['by_id'] : Array($filters['by_id']);
			foreach ($ids as $key=>$val) {
				$val=intval(trim($val));
				if ($val) {
					$ids[$key]=$val;
				} else {
					unset($ids[$key]);
				}
			}
			if ($ids) {
				$where.=' AND r.id IN ('.implode(',', $ids).')';
			}
		}
		
		if ($where) {
				$where='WHERE '.preg_replace('/^ AND/', '', $where);
		}
		
		
		$query_count='SELECT COUNT(*) as ct FROM reports AS r '.$where;
		$count=FW::$core->db->spam->query($query_count);

		if (!empty($count[0]['ct'])) {
			$count=$count[0]['ct'];
		} else {
			$count=0;
		}
		
		$data=Array();
		if ($count) {
			if ($paging_info['count']=='All') {$paging_info['count']=$count;}
			$where = str_replace('WHERE', 'AND', $where);
			$query_data='SELECT r.*, t.name AS task_name FROM reports AS r, tasks AS t WHERE r.task_id=t.id '.$where.' ORDER BY id DESC LIMIT '.$paging_info['from'].','.$paging_info['count'];
			$data=FW::$core->db->spam->query($query_data);
		}
		
		return Array('count'=>$count, 'data'=>$data);
	}

}
?>

<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class db {
    public function query($sql, $keys_from_field=false){
        $ret=Array();
        if (!$sql || !$this->_connection) {return false;}
        $res=mysql_query($sql, $this->_connection);
        if ( !$res ) {
            $this->error=mysql_error($this->_connection);
			print_r($this->error);
			echo '<br />SQL was: '.$sql;
			exit();
            return false;
        } elseif ($res!==true ) {
            if ( mysql_num_rows($res)>0 ) {
                while ( $iret = mysql_fetch_assoc($res) ) {
					if ( $keys_from_field && isset($iret[$keys_from_field]) ) {
						$ret[$iret[$keys_from_field]]=$iret;
					} else {
						$ret[]=$iret;
					}
				}
            }
        } else {
			return true;
		}
        return $ret;
    }

    public function escape($data) {
        if ( is_array($data) ) {
            foreach ($data as $key=>$val) {
                $data[$key]=mysql_real_escape_string($val);
            }
        } else {
            $data=mysql_real_escape_string($data);
        }
        return $data;
    }
    
    public function update($table, $data, $filter) {
		if (empty($data[$filter])){return false;}
		$query='UPDATE `'.$table.'` SET ';
		$filter_val=$data[$filter];
		unset($data[$filter]);
		$query.=$this->build_set_part($data);
		$query.='WHERE `'.$filter.'`=\''.$this->escape($filter_val).'\'';
		return $this->query($query);
	}
	
	public function insert($table, $data, $return_insert_id=true) {
		$query='INSERT INTO `'.$table.'` SET ';
		$query.=$this->build_set_part($data);
		$ret=$this->query($query);
		if ($ret && $return_insert_id) {
			$ret=$this->get_insert_id();
		}
		return $ret;	
	}
	
	public function insert_or_update($table, $data, $return_insert_id=true) {
		$query='INSERT INTO `'.$table.'` ';
		$query.=$this->build_values_keys_part($data);
		$query.=' ON DUPLICATE KEY UPDATE ';
		$query.=$this->build_set_part($data);
		$ret=$this->query($query);
		if ($ret && $return_insert_id) {
			$ret=$this->get_insert_id();
		}
		return $ret;	
	}	

    public function get_insert_id(){
        if (!$this->_connection) {return false;}
        return mysql_insert_id($this->_connection);
    }

    public function get_error(){
        return $this->error;
    }
    
    private function build_set_part($ar) {
		$ret='';
		foreach ($ar as $key=>$val) {
			if ($key=='id' && !$val) {
				continue;
			}
			$val=trim($val);
			if ( !is_numeric($val) ) {
				$val=$this->escape($val);
			}
			$ret.='`'.$key.'`=\''.$val.'\', ';
		}
		return preg_replace('/\, $/usi', ' ', $ret);
	}

    private function build_values_keys_part($ar) {
		$keys='(';
		$vals='(';
		foreach ($ar as $key=>$val) {
			if ($key=='id' && !$val) {
				continue;
			}
			$val=trim($val);
			if ( !is_numeric($val) ) {
				$val=$this->escape($val);
			}
			$keys.='`'.str_replace('`', '', $key).'`,';
			$vals.='\''.$val.'\',';
		}
		
		$keys=preg_replace('/,$/usi', '', $keys).') VALUES ';
		$vals=preg_replace('/,$/usi', '', $vals).')';
		return $keys.$vals;
	}   
}
?>

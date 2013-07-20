<?php
define('USE_FILES', false);
define('USE_MEMCACHED', true);
define('USE_MEMCACHEDB', false); //true is not implemented yet


class caching {
	private $mc_d;
	private $mc_db;
	private $cache_timeouts;
	private $tags=Array(
	);
	
	function __construct(){
		$cache_dir = dirname(__FILE__).'/caching_drivers/';
		if (USE_MEMCACHED){
			require_once $cache_dir.'cache_memcached.php';
			$mcd_servers[0]=SPAM_config::$MEMCACHED;
			$this->mc_d= new cache_memcached($mcd_servers);
		}
		if (USE_MEMCACHEDB){
			require_once $cache_dir.'cache_memcachedb.php';
			$this->mc_db= new cache_memcachedb();
		}
		if (USE_FILES){
			require_once $cache_dir.'cache_files.php';
			$this->mc_db= new cache_files();	
		}
	}
	
	public function get($namespace, $operation, $data_identifier){
		$key=$this->construct_key($namespace, $operation, $data_identifier);
		//debug::log(Array($namespace, $operation, $data_identifier));
		$res['data']=false;
		if (USE_MEMCACHED){
			$r_res=$this->mc_d->get($key);
			if ($r_res) {
				$res['data'] = $r_res;
				$res['tags'] = Array();
			}
			//debug::log($res);
		}
		if (!$res && USE_MEMCACHEDB){
			$res=$this->mc_db->get($key);
			if ($res){
				//memcached fault so we are restoring
				$this->mc_d->set($key, $res);
			}
		}
		if (USE_FILES){
			$this->files->get($key);
		}
		//invalidation data by tag expiration
		if ($res['data'] && $res['tags']){
			foreach ($res['tags'] as $tkey=>$tval){
				if ($this->get_tag($tkey)>$tval){
					$this->delete($namespace, $operation, $data_identifier);
					//debug::log('invalidated'.$namespace.$operation.$data_identifier.' by tag expiration');
					return false;
				}
			}
		}
		return $res['data'];
	}
	
	public function get_batch($namespace, $operation, $data_identifiers_array){
		$ret=Array();
		if (empty($data_identifiers_array)){return $ret;}
		foreach ($data_identifiers_array as $id){
			$res=$this->get($namespace, $operation, $id);
			if ($res){
				$ret[$id]=$res;
			}
		}
		return $ret;
	}
	
	public function delete($namespace, $operation, $data_identifier){
		$key=$this->construct_key($namespace, $operation, $data_identifier);
		if (USE_MEMCACHED){
			$this->mc_d->delete($key);
		}
		
		if (USE_MEMCACHEDB){
			$this->mc_db->delete($key);
		}
	}
	
	public function invalidate_tag($tag){
		$key=$this->construct_key($tag);
		$data=$this->get_tag_timestamp();
		$res=false;
		if (USE_MEMCACHED){
			$res=$this->mc_d->set($key, $data, 0, 0);
		}
		
		if (USE_MEMCACHEDB){
			$res=$this->mc_db->set($key, $data,  0, 0);
		}
		return $res;
	}
	
	private function get_tag($name){
		$key=$this->construct_key($name);
		$res=false;
		
		if (USE_MEMCACHED){
			$res=$this->mc_d->get($key);
		}
		if (!$res && USE_MEMCACHEDB){
			$res=$this->mc_db->get($key);
			if ($res){
				//memcached fault so we are restoring
				$this->mc_d->set($key, $res);
			}
		}
		
		return $res;
	}
	
	public function flush(){
		if (USE_MEMCACHED){
			$res=$this->mc_d->flush();
		}
		if (!$res && USE_MEMCACHEDB){
			$res=$this->mc_db->flush();
		}
	}

	public function set($namespace, $operation, $data_identifier, $data, $timeout=0){
		$key=$this->construct_key($namespace, $operation, $data_identifier);
		$data=Array('data'=>$data, 'tags'=>Array());
		
		$tag_timestamp=$this->get_tag_timestamp();
		if (isset($this->tags[$namespace][$operation])){
			foreach ($this->tags[$namespace][$operation] as $tag_key){
				$data['tags'][$tag_key]=$tag_timestamp;
			}
		}
		//debug::log(Array($namespace, $operation, $data_identifier, $data));
		$res=false;
		if (USE_MEMCACHED){
			$res=$this->mc_d->set($key, $data, 0, $timeout);
		}
		
		if (USE_MEMCACHEDB){
			$res=$this->mc_db->set($key, $data,  0, $timeout);
		}
		return $res;
	}
	
	private function construct_key($namespace, $operation='', $data_identifier=''){
		return md5($namespace.$operation.$data_identifier);
	}
	
	
	private function get_tag_timestamp(){
		return floor((microtime(true)*10000));
	}
	
}

//$mc=new Cache_manager(); $mc->set('geo', 'city', 'tag_all_regions_invalid', time());

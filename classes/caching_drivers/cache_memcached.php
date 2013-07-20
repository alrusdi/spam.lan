<?php

class cache_memcached {

    var $connections;
    var $_servers;

    function __construct($servers) {
        $this->_servers = $servers;
        // Attempt to establish/retrieve persistent connections to all servers.
        // If any of them fail, they just don't get put into our list of active
        // connections.
        $this->connections = array();
        for ($i = 0; $i < count($servers); $i++) {
            $server = $servers[$i];
            $con = memcache_connect($server['host'], $server['port']);
            //memcache_debug(1); 
            //$memcache = &new Memcache;
            //$con = $memcache->connect($server['host']);
            
            if (!($con == false)) {
                $this->connections[] = &$con;
            }else{
            	die('caching down');
            }
            
        }
    }

    function _getConForKey($key) {
		return $this->connections[0];
    }

    function debug($on_off) {
        $result = false;
        for ($i = 0; $i < count($this->connections); $i++) {
            if ($this->connections[$i]->debug($on_off)) $result = true;
        }
        return $result;
    }

    function flush() {
        $result = false;
        for ($i = 0; $i < count($this->connections); $i++) {
            if ($this->connections[$i]->flush()) $result = true;
        }
        return $result;
    }


    function get($key) {
        if (is_array($key)) {
            $dest = array();
            foreach ($key as $subkey) {
            $val = get($subkey);
            if (!($val === false)) $dest[$subkey] = $val;
            }
            return $dest;
        } else {
            $conn = &$this->_getConForKey($key);
            return $conn->get($key);
        }
    }

    function set($key, $var, $compress=0, $expire=0) {
        $conn = &$this->_getConForKey($key);
        $result = $conn->set($key, $var, $compress, (int)$expire);
        if (!$result) {
        	if (is_string($var)) error_log('Warning: memcached failed on saving data ('.strlen($var).'b)');
        	else error_log('Warning: memcached failed on saving data');
        }
        return $result;
    }

    function add($key, $var, $compress=0, $expire=0) {
        $conn = &$this->_getConForKey($key);
        return $conn->add($key, $var, $compress, (int)$expire);
    }

    function replace($key, $var, $compress=0, $expire=0) {
        $conn = &$this->_getConForKey($key);
        return $conn->replace($key, $var, $compress, (int)$expire);
    }

    function delete($key, $timeout=0) {
        $conn = &$this->_getConForKey($key);
        return $conn->delete($key, (int)$timeout);
    }

    function increment($key, $value=1) {
        $conn = &$this->_getConForKey($key);
        return $conn->increment($key, (int)$value);
    }

    function decrement($key, $value=1) {
        $conn = &$this->_getConForKey($key);
        return $conn->decrement($key, (int)$value);
    }

    function showStats($server=null) {
        $stats_out = '';
        if($server == null) {
            $i=0;
            foreach($this->connections as $conn) {  
                $server = $this->_servers[$i];
                $stats_array = memcache_get_stats($conn);
                $stats_out .= "</br><b>Server: ".$server['host'].": </b><br/>";
                foreach($stats_array as $key => $val) {
                    $stats_out .= "$key => $val <br/>";
                }   
                $i++;
            }
        }
        return $stats_out;
    }    
}

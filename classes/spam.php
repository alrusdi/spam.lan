<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class spam extends db {
	public $error=Array();
	public $_connection;
		
	public function __construct() {
        if (!$this->_connection && !$this->connect()) {
            die('Can\'t connect to database with given parameters. Check config.');
        }
    }
    
    public function connect(){
		$conn=mysql_connect(
							SPAM_config::$DB_SPAM['host'].':'.SPAM_config::$DB_SPAM['port'],
							SPAM_config::$DB_SPAM['user'],
							SPAM_config::$DB_SPAM['pass'],
							true
						);
		if ($conn) {
			$sel_db=mysql_select_db(SPAM_config::$DB_SPAM['name'], $conn);
			if (!$sel_db){
				mysql_close($conn);
				$conn=false;
			}
		}
		$this->_connection=$conn;
		return $conn;		
	}
}

?>

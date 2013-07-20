<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class nats extends db {
	public $error=Array();
	public $_connection;	
	
	public function __construct($settings=false) {
		if ( !$settings ) {
			$settings=SPAM_config::$DB_NATS;
		}
		
        if (!$this->_connection && !$this->connect($settings)) {
            die('Can\'t connect to database with given parameters. Check config.');
        }
    }
    
    public function connect($settings){
		$conn=mysql_connect(
							$settings['server'].':'.$settings['port'],
							$settings['user'],
							$settings['password'],
							true
						);	
        if ($conn) {
            $sel_db=mysql_select_db(SPAM_config::$DB_NATS['name'], $conn);
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

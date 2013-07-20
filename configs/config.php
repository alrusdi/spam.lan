<?php

class SPAM_config {
	public static $DB_NATS = Array (); //will be filled from database
	public static $DB_SPAM = Array(
			'name' => 'spam',
			'user' => 'root',
			'pass' => '',
			'host' => 'localhost',
			'port' => '3306'		
	);
	
	public static $paging=Array(
		'upperlimit' => 10,
		'max_page_numbers_per_page' => 10
	);
	public static $MEMCACHED = Array(
		'host' => 'localhost',
		'port' => 11211 
	);
	public static $smarty = Array(
		'caching' => false,
		'cache_lifetime' => 120,
		'template_dir' => '/views/', //will be prepended with ROOT_PATH
		'compile_dir' => '/views/cache/', //will be prepended with ROOT_PATH
	);
	public static $BASE_URL='http://spam.lan/';
	public static $COOKIE_PATH='/';
	public static $GET_INSTANCE_METHOD='get_instance';
	public static $STATUSES=false;
}
?>

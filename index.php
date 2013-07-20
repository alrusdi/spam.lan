<?php
define('SPAM_BASE', 1);
//loading config file
$root=dirname(__FILE__);

if ( !is_file($root.'/configs/config.php') ) {
    die('Config file is not found');
}
$config=require_once($root.'/configs/config.php');

define('ROOT_PATH', $root);
define('BASE_URL', SPAM_config::$BASE_URL);

require_once($root.'/classes/framework.php');
new FW();
//TODO - cache it
$settings = FW::$core->settings->get(true);
if ( current($settings['nats_db_settings']) ) {
	$ns=current($settings['nats_db_settings']);
	SPAM_config::$DB_NATS = $ns;
}

$path=FW::$core->url->parse($_SERVER['REQUEST_URI']);
//finding appropriate controller for url and run it
FW::$core->base_controller->run($path);
?>

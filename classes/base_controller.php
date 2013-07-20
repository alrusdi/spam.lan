<?php
if ( !defined('SPAM_BASE') ) { die('Direct access is not allowed'); }

class base_controller {
	public static $tpl=false;
	public $current_action;
	public $current_request;
	
    public function run($request=Array()) {
        //if undefined controller we assume index controller
        if ( empty($request[0]) ){
            $request[0]='index';
        }

        //if undefined action we assume index action
        if ( empty($request[1]) ){
            $request[1]='index';
        }
        //checking if needed controller is exists
        if ( is_file(ROOT_PATH.'/controllers/'.strtolower($request[0]).'.php' ) ){
            require_once(ROOT_PATH.'/controllers/'.strtolower($request[0]).'.php');
        } else {
            self::show_error(404);
        }

        //calling requested method in controller class
        if ( method_exists($request[0].'_controller', $request[1]) ) {
			session_set_cookie_params(0, SPAM_config::$COOKIE_PATH, '.'.$_SERVER['SERVER_NAME']);
			session_start();
			if ($request[0]!='login' && $request[0]!='unsubscribe') {
				$this->check_login();
			}
			
            $class=$request[0].'_controller';
			require(ROOT_PATH.'/libs/smarty/Smarty.class.php');
			$smarty = new Smarty;
			//$smarty->force_compile = true;
			//$smarty->debugging = true;
			$smarty->caching = SPAM_config::$smarty['caching'];
			$smarty->cache_lifetime = SPAM_config::$smarty['cache_lifetime'];
			$smarty->template_dir=ROOT_PATH.SPAM_config::$smarty['template_dir'];
			$smarty->compile_dir=ROOT_PATH.SPAM_config::$smarty['compile_dir'];
			//die(ROOT_PATH.'/views/');
			$smarty->assign('CURRENT_REQUEST', $request);
			$this->current_request=$request;	
			self::$tpl=$smarty;

            $ctrlr=new $class();
            call_user_func_array(
                        array(
                            $ctrlr,
                            $request[1]
                        ),
                        array_slice($request, 2) 
                    );
        } else {
            self::show_error('404');
        }
    }
    
    public static function check_login(){
		if (empty($_SESSION['current_user'])){
			header('Location: '.BASE_URL.'login/');	
			die();
		}
	}

    private static function show_error($error=404) {
        echo($error);
        die();
    }
}

?>

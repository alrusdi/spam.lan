<?php

class FW_loader {
	private $class_type;
	private $calling_classes=Array();
	function __construct($class_type){
		$this->class_type=$class_type;
	}
	
	function __call($method_name, $args){
		/*
		print_r(
			Array(
				'method'=>$method_name,
				'args'=>$args,
				'classes'=>$this->calling_classes
			)
		);
		*/
		$class_to_build=array_pop($this->calling_classes);
		$this->calling_classes=Array();
		//TODO different constructor args must create different objects
		if (!empty($args['FW_CONSTRUCTOR_ARGUMENTS'])) {
			unset($args['FW_CONSTRUCTOR_ARGUMENTS']);
		}
		$return_only_instance=($method_name==SPAM_config::$GET_INSTANCE_METHOD);
		//TODO we need here ability to have more than one object of given class

		$obj=FW::get_class_instance($class_to_build);
		
		if (!$obj) {
			if ( ($return_only_instance && class_exists($class_to_build)) || method_exists($class_to_build, $method_name) ) {
				$obj=new $class_to_build();
				FW::set_class_instance($obj, $class_to_build);
			} else {
				error_log('Framework cant call the method "'.$class_to_build.'"::"'.$method_name.'"');
				exit();
			}
		}

		if ($return_only_instance){ return $obj; }
		
		return call_user_func_array(
					array(
						$obj,
						$method_name
					),
					$args 
				);
	}
	
	function __get($class_name){
		switch ($this->class_type){
			case 'core':
				$res=$this->include_class(ROOT_PATH.'/classes/', $class_name);
				break;
		}
		if (!$res) {
			error_log('Framework cant include the class:'.$this->class_type.'::'.$class_name);
			exit();
		}
		array_push($this->calling_classes, $class_name);
		return $this;
	}
	
	private function include_class($path, $class_name){	
		if (!file_exists($path.$class_name.'.php')){
			return false;
		}	
		if ( !FW::get_class_instance($class_name) ) {
			require_once($path.$class_name.'.php');
		}
		return true;
	}
}

class FW {
	//available class types (like namespaces)
	public static $core;
	public static $lib;
	public static $ctrl;

	//other class vars (must be also listed in $vars_not_class_types)
	private $vars_not_class_types=Array('vars_not_class_types', 'still_loaded');
	public static $still_loaded=Array();
	
	function __construct(){
		$class_types = get_class_vars(get_class($this));
		foreach($class_types as $key=>$val){
			if ( !in_array($key, $this->vars_not_class_types) ) {
				self::${$key} = new FW_loader($key);
			}
		}
	}
	
	public function get_class_instance($class_name, $constructor_args=Array()){
		if (!empty(self::$still_loaded[$class_name])){
			return self::$still_loaded[$class_name];
		}
		return false;
	}
	
	public function set_class_instance($instance, $class_name, $constructor_args=Array()){
		self::$still_loaded[$class_name]=$instance;
	}
}

?>

<?php 
namespace Svbk\WP\Helpers\Form\Action;

use Svbk\WP\Helpers\Form\Form;

abstract class FormAction {
    
	public static function register( $options = array() ) {

		$class = get_called_class();

		$instance = new $class($options);

		return $instance;
	}

	
	public function __construct( $properties = array() ) {
		$this->config( $properties );
	}
	
	public function config( $config ) {
        if( !empty($properties) ){
    		foreach ( $properties as $property => $value ) {
    				$form->$property = $value;
    		}
        }		
	}
	
	protected function executeCallbacks( &$params, $attrs ) {
	
		foreach ( $params as $key => &$param ) {
		    if( is_callable( $param ) ) {
		        $param = call_user_func_array( $param, $attrs );
		    }
		}	
		
	}
    
    abstract public function doAction( Form $form );
}
<?php
namespace Svbk\WP\Helpers\Form\Action\Mailchimp;

use Svbk\WP\Helpers\Form\Action\FormAction;
use Svbk\WP\Helpers\Mailing\MailChimp;
use Svbk\WP\Helpers\Form\Form;

class Subscribe extends FormAction {
 
	public $apikey = '';
	public $list_id = '';
	public $update = false;    
	public $attributes = array();    
 
    public $mergeTags = array();
    public $mergeTagMethod = 'subscribeMergeTags';
 
    public function doAction( Form $form ){

		if ( ! empty( $this->apikey ) && ! empty( $this->list_id ) ) {
			$mc = new MailChimp( $this->apikey );
			$errors = $mc->subscribe( $this->list_id, trim( $form->getInput( 'email' ) ), $this->attributes( $form ), $this->update );
			array_walk( $errors, array( $form, 'addError' ) );
		}        
        
    }
    
	protected function mergeTags( $form ){
	    
	    $mergeTags = array();
	    
	    if( is_callable($this->mergeTags) ) {
	        $mergeTags = call_user_func();    
	    } elseif( is_array( $this->mergeTags ) && !empty( $this->mergeTags ) ) {
	        $mergeTags = $form->mergeTags; 
	    } 
	    
	    if( method_exists( $form, $this->mergeTagMethod ) ) {
	        $mergeTags = array_merge( $mergeTags, call_user_func( array($form , $this->mergeTagMethod ) ) );
	    }	    
	    
	    if( empty( $mergeTags ) ) {
	        $mergeTags = preg_filter('/^/', 'INPUT_', $this->inputData);
	    }
	    
	    return $mergeTags;
	}
    
	protected function attributes($form) {
		return array_merge_recursive(
			$this->attributes,
			array(
				'merge_fields' => $this->mergeTags( $form ),
			)
		);
	}    
    
}
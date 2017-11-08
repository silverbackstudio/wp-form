<?php 

namespace Svbk\WP\Helpers\Form\Action\Mandrill;

use Svbk\WP\Helpers\Form\Action\FormAction;
use Svbk\WP\Helpers\Form\Form;

class Send extends FormAction {

	public $apikey = '';
	public $template = '';
	
	public $recipient_email = 'webmaster@silverbackstudio.it';
	public $recipient_name = 'Webmaster';

    public $field_email = 'email';
    public $field_message = 'request';

    public $mergeTags = array();
    public $mergeTagMethod = 'subscribeMergeTags';

	public $message = array();

    public function doAction( Form $form ){
        
		if ( ! empty( $this->apikey ) ) {

			try {
				$mandrill = new Mandrill( $this->apikey );

                $params = $this->messageParams( $form );
                $results = array();

				if ( $this->template ) {
					$results = $mandrill->messages->sendTemplate( $this->template, array(), $params );
				} elseif( !empty( $params['text'] ) || !empty( $params['html'] ) ) {
					$results = $mandrill->messages->send( $params );
				}
				
				if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
					throw new Mandrill_Error( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-form' ) );
				}

				$errors = $mandrill->getResponseErrors( $results );

				foreach ( $errors as $error ) {
					$form->addError( $error, $this->field_email );
				}

			} catch ( Mandrill_Error $e ) {
				$form->addError( $e->getMessage() );
			}
			
		}
    }
    
	protected function getRecipients( Form $form ) {
		$recipents =  array( );
	
		if( $this->recipient_email ) {
    		$recipients[] =	array(
    			'email' => trim( $this->recipient_email ),
    			'name' => $this->recipient_name,
    			'type' => 'to',
    		);
		} else {
    		$recipients[] =	array(
    			'email' => get_option('admin_email'),
    			'name' => '',
    			'type' => 'to',
    		);		    
		}
		
		return $recipents;
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
	
	protected function messageParams( Form $form ) {
	    
        $params = array_merge(
			array(
				'to' => $this->getRecipients( $form ),
				'global_merge_vars' => Mandrill::castMergeTags( $this->mergeTags( $form ) ),
				'metadata' => array(
					'website' => home_url( '/' ),
				),
				'merge' => true,
			),
			$this->message			
		);
		
		//$this->executeCallbacks( $params, array($form, $this) ) ;
		
        if( empty($params['text']) && $form->getInput( $this->field_request ) ) {
		    $params['text'] = $form->getInput( $this->field_request ) ;
		}
		
	}    
    
}
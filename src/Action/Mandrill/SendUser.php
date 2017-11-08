<?php 

namespace Svbk\WP\Helpers\Form\Action\Mandrill;

use Svbk\WP\Helpers\Form\Action\FormAction;
use Svbk\WP\Helpers\Form\Form;

class SendUser extends Send {

    public $field_name = 'fname';

	protected function getRecipients( Form $form ) {

    	return array(
				'email' => trim( $form->getInput( $this->field_email ) ),
				'name' => ucfirst( $form->getInput( $this->field_name ) ),
    			'type' => 'to',
    		);		    
		}
		
	}
    
}
<?php
namespace Svbk\WP\Helpers\Form;

use Svbk\WP\Helpers\Mailing\Mandrill;
use Mandrill_Error;

class Contact extends Subscribe {

	public $field_prefix = 'cnt';

	public function setInputFields( $fields = array() ) {

		return parent::setInputFields(
			array_merge(
				array(
					'request' => array(
						'required' => true,
						'label' => __( 'Message', 'svbk-form' ),
						'type' => 'textarea',
						'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
						'error' => __( 'Please write a brief description of your request', 'svbk-form' ),
					),
				),
				$fields
			)
		);

	}

	protected function addActions(){
		parent::addActions();

		$this->addAction( 'send', new Action\Mandrill\Send(), 10 );
	}

	public function emailMergeTags(){
		return preg_filter('/^/', 'INPUT_', $this->inputData);
	}	

}

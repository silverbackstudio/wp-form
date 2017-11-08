<?php
namespace Svbk\WP\Helpers\Form;

use Svbk\WP\Helpers\Mailing\MailChimp;

class Subscribe extends Submission {

	public $field_prefix = 'sbs';

	protected function addActions(){
		$this->addAction( 'subscribe', new Action\Mailchimp\Subscribe() , 11 );
	}

	public function subscribeMergeTags(){
		return array( 
			'FNAME' => $this->getInput( 'fname' ),
			'MARKETING' => $this->getInput( 'policy_directMarketing' ) ? 'yes' : 'no',
		);
	}

}

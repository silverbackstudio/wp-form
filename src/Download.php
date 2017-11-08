<?php
namespace Svbk\WP\Helpers\Form;

use Svbk\WP\Helpers\Mailing\Mandrill;
use Mandrill_Error;

class Download extends Subscribe {

	public $field_prefix = 'dl';

	public function processInput( $input_filters = array() ) {

		$input_filters['fid'] = FILTER_VALIDATE_INT;

		return parent::processInput( $input_filters );
	}

	protected function addActions(){
		
		parent::addActions();
		
		$this->addAction('send_download', new Actions\Mandrill\SendUser(), 10 );
	}

	protected function emailMergeTags() {

		$mergeTags = preg_filter('/^/', 'INPUT_', $this->inputData);
		$mergeTags['DOWNLOAD_URL'] = esc_url( $this->getDownloadLink() );

		return $mergeTags;
	}

	protected function getDownloadLink() {
		return wp_get_attachment_url( $this->getInput( 'fid' ) );
	}

	public function renderParts( $attr = array() ) {

		$output = parent::renderParts( $attr );
		$output['input']['file'] = '<input type="hidden" name="' . $this->fieldName( 'fid' ) . '" value="' . $attr['file'] . '" >';

		return $output;
	}

	protected function validateInput() {

		parent::validateInput();

		$post = get_post( (int) $this->getInput( 'fid' ) );

		if ( ! $post || ('attachment' != $post->post_type) ) {
			$this->addError( __( 'The specified download doesn\'t exists anymore. Please contact site owner', 'svbk-form' ) );
		}

	}

}

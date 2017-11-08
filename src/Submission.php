<?php
namespace Svbk\WP\Helpers\Form;

use Svbk\WP\Helpers;

class Submission extends Form {

	public static $defaultPolicyFilter = array(
		'filter' => FILTER_VALIDATE_BOOLEAN,
		'flags' => FILTER_NULL_ON_FAILURE,
	);

	public $field_prefix = 'sub';
	public $action = 'submission';
	public $submitUrl = '';

	public $inputFields = array();
	public $policyParts = array();

	protected $inputData = array();
	protected $inputErrors = array();

	public $actions = array();

	public $hardRedirect = false;
	public $redirectTo;

	public static $next_index = 1;
	public $index = 0;

	public function __construct() {

		$this->index = self::$next_index++;

		$this->setInputFields( $this->inputFields );
		$this->setPolicyParts( $this->policyParts );

		add_action( 'init', array( $this, 'processSubmission' ) );
		
		$this->addActions();
	}

	public function setInputFields( $fields = array() ) {

		$this->inputFields = array_merge(
			array(
				'fname' => array(
					'required' => true,
					'label' => __( 'First Name', 'svbk-form' ),
					'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
					'error' => __( 'Please enter first name', 'svbk-form' ),
				),
				'email' => array(
					'required' => true,
					'label' => __( 'Email Address', 'svbk-form' ),
					'filter' => FILTER_SANITIZE_EMAIL,
					'error' => __( 'Invalid email address', 'svbk-form' ),
				),
			),
			$fields
		);

		return $this->inputFields;
	}

	public function insertInputField( $fieldName, $fieldParams, $after = null ) {

		if ( $after ) {
			$this->inputFields = Helpers\Form\Renderer::arrayKeyInsert( $this->inputFields, array(
				$fieldName => $fieldParams,
			), $after );
		} else {
			$this->inputFields[ $fieldName ] = $fieldParams;
		}

	}

	public function addInputFields( $fields, $key = '', $position = 'after' ) {
		$this->inputFields = Helpers\Form\Renderer::arraykeyInsert( $this->inputFields, $fields, $key, $position );
	}

	public function removeInputFields() {
		$this->inputFields = array();
	}
	
	public function removeInputField( $field ) {
		if ( array_key_exists( $field, $this->inputFields ) ) {
			unset( $this->inputFields[$field] );
		}
	}	

	public function setPolicyParts( $policyParts = array() ) {

		$this->policyParts = array_merge_recursive(
			array(
				'policy_service' => array(
					'label' => __( 'I have read and agree to the "Terms and conditions" and the "Privacy Policy"', 'svbk-form' ),
					'required' => true,
					'type' => 'checkbox',
					'error' => __( 'Privacy Policy terms must be accepted', 'svbk-form' ),
					'filter' => self::$defaultPolicyFilter,
				),
				'policy_newsletter' => array(
					'label' => __( 'I accept the processing of the data referred to in Article 1 of the "Privacy policy"', 'svbk-form' ),
					'required' => false,
					'type' => 'checkbox',
					'filter' => self::$defaultPolicyFilter,
				),
				'policy_directMarketing' => array(
					'label' => __( 'I accept the processing of the data referred to in Article 2 of the "Privacy policy"', 'svbk-form' ),
					'type' => 'checkbox',
					'required' => false,
					'filter' => self::$defaultPolicyFilter,
				),
			),
			$policyParts
		);

		return $this->policyParts;
	}

	public function getInput( $field ) {
		return isset( $this->inputData[ $field ] ) ? $this->inputData[ $field ] : null;
	}
	
	public function processInput( $input_filters = array() ) {

		$index = filter_input( INPUT_POST, 'index', FILTER_VALIDATE_INT );

		if ( $index === false ) {
			$this->addError( __( 'Input data error', 'svbk-form' ) );
			return;
		} else {
			$this->index = $index;
		}

		$input_filters['policy_all'] = self::$defaultPolicyFilter;

		$input_filters = array_merge(
			$input_filters,
			wp_list_pluck( $this->inputFields, 'filter' )
		);

		if ( $this->policyParts ) {
			$input_filters = array_merge(
				$input_filters,
				wp_list_pluck( $this->policyParts, 'filter' )
			);
		}

		$this->inputData = parent::processInput( $input_filters );

		$this->validateInput();

	}

	protected function getField( $fieldName ) {

		if ( isset( $this->inputFields[ $fieldName ] ) ) {
			return $this->inputFields[ $fieldName ];
		} elseif ( isset( $this->policyParts[ $fieldName ] ) ) {
			return $this->policyParts[ $fieldName ];
		} else {
			return false;
		}

	}

	protected function validateInput() {

		$policyFields = array_keys( $this->policyParts );

		foreach ( $this->inputData as $name => $value ) {

			$field = $this->getField( $name );

			if ( ! $value && $this->fieldRequired( $field ) ) {
				$this->addError( $this->fieldError( $field, $name ), $name );

				if ( in_array( $name, $policyFields ) ) {
					$this->addError( $this->fieldError( $field, $name ), 'policy_all' );
				}
			}
		}

	}

	public function checkPolicy( $policyPart = 'policy_service' ) {

		if ( $this->getInput( 'policy_all' ) ) {
			return true;
		}

		if ( $this->getInput( $policyPart ) ) {
			return true;
		}

		return false;
	}
	
	public function confirmMessage() {
		return $this->confirmMessage ?: __( 'Thanks for your request, we will reply as soon as possible.', 'svbk-form' );
	}	

	public function processSubmission() {

		if ( filter_input( INPUT_GET, 'svbkSubmit', FILTER_SANITIZE_SPECIAL_CHARS ) !== $this->action ) {
			return;
		}

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'Content-Type: application/json' );
		send_nosniff_header();

		$this->processInput();

		if ( empty( $this->errors ) && $this->checkPolicy() ) {
			$this->doActions();
		}

		echo $this->formatResponse();
		exit;		
	}
	
	public function formatResponse( $errors ) {

		if ( ! empty( $this->errors ) ) {

			return json_encode(
				array(
					'prefix' => $this->field_prefix,
					'status' => 'error',
					'errors' => $this->errors,
				)
			);

		}

		$response = array(
			'prefix' => $this->field_prefix,
			'status' => 'success',
			'message' => $this->confirmMessage(),
		);

		if ( ! $this->hardRedirect && $this->redirectTo ) {
			$response['redirect'] = $this->redirectTo;
		}

		return json_encode( $response );
	}	
	
	protected function submitUrl() {

		return home_url(
			add_query_arg(
				array(
					'svbkSubmit' => $this->action,
				)
			)
		);

	}	
	
	public static function hookName(){
		return strtolower( get_called_class() );
	}

	protected function addActions(){}

	public function addAction( $handle, &$action, $priority = 10 ){ 
		
		$this->actions[$handle] = $action;
		
		add_action(self::hookName(), array($action, 'doAction'), $priority);
		
		return $this;
	}
	
	public function configureAction($handle, $config){
		
		if( empty( $this->actions[$handle] ) ) {
			return false;
		}
		
		$this->actions[$handle]->config( $config );
	
	}
	
	public function removeAction( $handle, $priority = 10 ){ 
		
		unset( $this->actions[$handle] );
		
		remove_action(self::hookName(), array($this->actions[$handle], 'doAction'), $priority);
		
		return $this;
	}	

	protected function doActions(){ 
		do_action(self::hookName(), $this);
	}

	protected function privacyNotice( $attr ) {

		$label = __( 'Privacy policy', 'svbk-form' );

		if ( shortcode_exists( 'privacy-link' ) ) {
			$privacy = do_shortcode( sprintf( '[privacy-link]%s[/privacy-link]', $label ) );
		} elseif ( isset( $attr['privacy_link'] ) && $attr['privacy_link'] ) {
			$privacy = sprintf( __( '<a href="%1$s" target="_blank">%2$s</a>', 'svbk-form' ), $attr['privacy_link'], $label );
		} else {
			$privacy = $label;
		}

		$text = sprintf( __( 'I declare I have read and accept the %s notification and I consent to process my personal data.', 'svbk-form' ), $privacy );

		if ( count( $this->policyParts ) > 1 ) {
			$flagsButton = '<a class="policy-flags-open" href="#policy-flags-' . $this->index . '">' . __( 'click here','svbk-form' ) . '</a>';
			$text .= '</label><label class="show-policy-parts">' . sprintf( __( 'To select the consents partially %s.', 'svbk-form' ), $flagsButton );
		}

		return $text;
	}

	public function renderParts( $attr = array() ) {

		$output = array();

		$form_id = $this->field_prefix . self::PREFIX_SEPARATOR . $this->index;

		$output['formBegin'] = '<form class="svbk-form" action="' . esc_url( $this->submitUrl() . '#' . $form_id ) . '" id="' . esc_attr( $form_id ) . '" method="POST">';

		foreach ( $this->inputFields as $fieldName => $fieldAttr ) {
			$output['input'][ $fieldName ] = $this->renderField( $fieldName, $fieldAttr );
		}

		$output['requiredNotice'] = '<div class="required-notice">' . __( 'Required fields', 'svbk-form' ) . '</div>';

		$output['policy']['begin'] = '<div class="policy-agreements">';

		if ( count( $this->policyParts ) > 1 ) {

			$output['policy']['global'] = $this->renderField( 'policy_all', array(
					'label' => $this->privacyNotice( $attr ),
					'type' => 'checkbox',
					'class' => 'policy-flags-all',
				)
			);
			$output['policy']['flags']['begin'] = '<div class="policy-flags" id="policy-flags-' . $this->index . '" style="display:none;" >';

			foreach ( $this->policyParts as $policy_part => $policyAttr ) {
				$output['policy']['flags'][ $policy_part ] = $this->renderField( $policy_part, $policyAttr );
			}

			$output['policy']['flags']['end'] = '</div>';

		} else {
			$output['policy']['global'] = $this->renderField( 'policy_service', array(
				'label' => $this->privacyNotice( $attr ),
				'type' => 'checkbox',
				)
			);
		}

		$output['policy']['end'] = '</div>';
		$output['input']['index']  = '<input type="hidden" name="index" value="' . $this->index . '" >';
		$output['submitButton'] = '<button type="submit" name="' . $this->fieldName( 'subscribe' ) . '" class="button">' . urldecode( $attr['submit_button_label'] ) . '</button>';
		$output['messages'] = '<div class="messages"><ul></ul><div class="close"><span>' . __( 'Close', 'svbk-form' ) . '</span></div></div>';
		$output['formEnd'] = '</form>';

		return $output;
	}

}

<?php

/**
 * @package Silverback Forms
 * @version 1.0
 */

/**
Plugin Name: Silverback Forms
Plugin URI: https://gitlab.com/silverbackstudio/wp-form
Description: Silverback Form Classes
Author: Silverback Studio
Version: 1.0
Author URI: http://www.silverbackstudio.it/
Text Domain: svbk-form
 */


function svbk_form_init() {
	load_plugin_textdomain( 'svbk-form', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'svbk_form_init' );

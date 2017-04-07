<?php

/**
* The plugin bootstrap file
*
* This file is read by WordPress to generate the plugin information in the plugin
* admin area. This file also includes all of the dependencies used by the plugin,
* registers the activation and deactivation functions, and defines a function
* that starts the plugin.
*
* @link
* @since             1.0.0
* @package           Web api test
*
* @wordpress-plugin
* Plugin Name:       Web api test
* Plugin URI:
* Description:       This is a plugin for wp-crud-test
* Version:           1.0.0
* Author:
* Author URI:
* License:           MIT
* License URI:
* Text Domain:       wp-crud-test
* Domain Path:       /languages
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
* Import wp-crud and wp-crud-test-client
*/
require( plugin_dir_path( __FILE__ ) . '/crud-controller.php' );
require( plugin_dir_path( __FILE__ ) . '/crud.php' );

/**
* The code that runs during plugin activation.
* This action is documented in includes/class-plugin-name-activator.php
*/
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'crud-dao.php';
	Crud_dao::activate();
	crud_test_deactivate();
}

/**
* The code that runs during plugin deactivation.
* This action is documented in includes/class-plugin-name-deactivator.php
*/
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'crud-dao.php';
	Crud_dao::deactivate();
	require_once plugin_dir_path( __FILE__ ) . 'crud-dao.php';
	crud_test_activate();
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );


add_action( 'rest_api_init', function(){
	$controller = new Crud_Controller;
	$controller->register_routes();
} );


/**
* Begins execution of the plugin.
*
* Since everything within the plugin is registered via hooks,
* then kicking off the plugin from this point in the file does
* not affect the page life cycle.
*
* @since    1.0.0
*/
function run_plugin_web_api() {
	crud_test_run();
}
run_plugin_web_api();

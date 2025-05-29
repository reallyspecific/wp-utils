<?php
/**
 * Plugin Name: Sample Plugin
 * Description: A plugin for testing the WP Utils library.
 * Version: 1.0
 * Author: Floating Point Operations, LLC
 * Author URI: https://github.com/reallyspecific
 * Text Domain: sample-plugin
 * Domain Path: /languages
 */

namespace ReallySpecific\SamplePlugin;

function setup() {

	require_once __DIR__ . '/inc/utils/load.php';

	spl_autoload_register( function ( $class_name ) {
		Utils\class_loader( $class_name, __DIR__ . '/classes', __NAMESPACE__ );
	} );

	new Plugin( [ 'name' => 'Sample Plugin' ] );

}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );
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

use function ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Plugin;

if ( ! is_defined( 'ABSPATH' ) ) {
	return;
}

function &plugin(): Plugin {

	static $plugin = null;
	if ( $plugin ) {
		return $plugin;
	}

	require_once __DIR__ . '/dependencies/reallyspecific/wp-utils/load.php';

	$plugin = new Plugin( [ 'name' => 'Sample Plugin', 'file' => __FILE__ ] );

	return $plugin;
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\plugin' );
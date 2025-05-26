<?php
/**
 * Utility library for WordPress plugins and themes.
 * @package ReallySpecific\WP_Util
 * @since 0.1.0
 */

namespace ReallySpecific\WP_Util;

function setup() {
	autoload_directory( __DIR__ . '/functions' );
	spl_autoload_register( __NAMESPACE__ . '\\class_loader' );
}

/**
 * Requires all php files in a given directory
 *
 * @param mixed $abs_path
 * @return void
 */
function autoload_directory( $abs_path ) {
	$files = glob( trailingslashit( $abs_path ) . '*.php' );
	foreach ( $files as $file ) {
		include_once $file;
	}
}

/**
 * Loads a utility class file if the class does not already exist.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function class_loader( string $class_name )
{
	if ( class_exists( $class_name ) ) {
		return;
	}

	$class_name = str_replace( __NAMESPACE__ . '\\', '', $class_name );
	include_once __DIR__ . '/classes/' . str_replace( '\\', '/', $class_name ) . '.php';
}

function assets_dir() {
	return __DIR__ . '/assets';
}

function is_debug_mode() {
	return  ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'RS_UTIL_DEBUG' ) && RS_UTIL_DEBUG );
}

if ( defined( 'ABSPATH' ) ) {
	setup();
}
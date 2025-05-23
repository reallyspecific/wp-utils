<?php
/**
 * Utility library for WordPress plugins and themes.
 * @package ReallySpecific\WP_Util
 * @since 0.1.0
 */

namespace ReallySpecific\WP_Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function setup() {
	autoload_directory( __DIR__ . '/functions' );
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
function class_loader(string $class_name, $namespace = null, $loader_path = null)
{
	if ( is_null($namespace) ) {
		$namespace = __NAMESPACE__;
	}
	if ( class_exists( $namespace . '\\' . $class_name ) ) {
		return;
	}
	if ( is_null($loader_path) ) {
		$loader_path = __DIR__ . '/classes';
	} else {
		$loader_path = untrailingslashit($loader_path);
	}
	require_once $loader_path . "/{$class_name}.php";
}

function assets_dir() {
	return __DIR__ . '/assets';
}

function is_debug_mode() {
	return  ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'RS_UTIL_DEBUG' ) && RS_UTIL_DEBUG );
}
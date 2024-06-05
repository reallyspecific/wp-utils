<?php

namespace ReallySpecific\WP_Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . "/functions/text.php";
require_once __DIR__ . "/functions/posts.php";

/**
 * Loads a utility class file if the class does not already exist.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function class_loader( string $class_name, $namespace = null, $loader_path = null ) {
	if ( is_null( $namespace ) ) {
		$namespace = __NAMESPACE__;
	}
	if ( class_exists( $namespace . '\\' . $class_name ) ) {
		return;
	}
	if ( is_null( $loader_path ) ) {
		$loader_path = __DIR__ . '/classes';
	} else {
		$loader_path = untrailingslashit( $loader_path );
	}
	require_once $loader_path . "/{$class_name}.php";
}

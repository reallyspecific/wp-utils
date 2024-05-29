<?php

namespace ReallySpecific\WP_Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . "/functions/posts.php";

/**
 * Loads a utility class file if the class does not already exist.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function class_loader( string $class_name ) {
	if ( class_exists( __NAMESPACE__ . '\\' . $class_name ) ) {
		return;
	}
	require_once __DIR__ . "/classes/{$class_name}.php";
}

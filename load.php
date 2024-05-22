<?php

namespace ReallySpecific\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . "/functions.php";

/**
 * Loads a utility class file if the class does not already exist.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function maybe_load( string $class_name ) {
	if ( class_exists( __NAMESPACE__ . '\\' . $class_name ) ) {
		return;
	}
	require_once __DIR__ . "/{$class_name}.php";
}

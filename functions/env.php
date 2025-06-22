<?php

namespace ReallySpecific\Utils\Environment;

use ReallySpecific\Utils\MultiArray;

function &_global(): MultiArray {
	static $vars = new MultiArray();
	return $vars;
}

function add_global_var( $key, $value = null ) {
	$global = _global();
	$global[ $key ] = $value;
	return $global;
}

function get_global_var( $key ) {
	$global = _global();
	return $global[ $key ];
}

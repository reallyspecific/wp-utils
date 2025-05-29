<?php

namespace ReallySpecific\Utils\Tests;

use WP_Mock;

use function ReallySpecific\Utils\autoload_directory;

function config( $key ) {
	switch( $key ) {
		case 'root_dir':
			return dirname( __DIR__ );
		case 'tests_dir':
			return __DIR__;
		default:
			return null;
	}
}

require_once config( 'root_dir' ) . '/vendor/autoload.php';
require_once config( 'root_dir' ) . '/load.php';

autoload_directory( __DIR__ . '/../functions' );

require_once config( 'tests_dir' ) . '/bootstrap/helpers.php';
Bootstrap\build_sample_plugin();

WP_Mock::bootstrap();
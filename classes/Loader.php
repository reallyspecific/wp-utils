<?php

namespace ReallySpecific\Utils;

use ReallySpecific\Utils\setup;

class Loader {

	public function __construct() {
		if ( ! function_exists( 'ReallySpecific\Utils\setup' ) ) {
			require_once __DIR__ . '/../load.php';
			setup();
		}
	}

}
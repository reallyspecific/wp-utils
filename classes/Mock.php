<?php

namespace ReallySpecific\Utils;

use WP_Mock;

class Mock extends WP_Mock {

	static Functions $functions;
	public static function setUp(): void {
		parent::setUp();
		if ( empty( static::$functions ) ) {
			static::$functions = new Functions();
		}
	}

	public static function didAction( $action ) {
		return static::$event_manager->called( $action );
	}

}
<?php

namespace ReallySpecific\Utils;

use ReallySpecific\Utils\Mocks\Functions;

class Mock extends \WP_Mock {

	static Functions $functions;

	public static function bootstrap(): void
	{
		parent::bootstrap();

	}

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
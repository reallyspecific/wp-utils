<?php

/**
 * Provides mocking utilities for usage in testing environments,
 * extending the functionality of WP_Mock and supporting additional customization.
 */

namespace ReallySpecific\Utils;

use ReallySpecific\Utils\Mocks\Functions;

/**
 * Extends the functionality of the WP_Mock class to include additional mock capabilities.
 * Provides methods to bootstrap and set up mock functions and register actions.
 */
class Mock extends \WP_Mock {

	/**
	 *  Extended function instance
	 *
	 *  @var Functions
	 */
	static Functions $more_functions;

	/**
	 * Initializes the application by calling the parent bootstrap method
	 * and performing additional setup specific to this class.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		parent::bootstrap();
		static::setUp();
	}

	/**
	 * Initializes and configures necessary static properties.
	 *
	 * @return void
	 */
	public static function setUp(): void {
		if ( empty( static::$more_functions ) ) {
			static::$more_functions = new Functions();
		}
	}



	public static function didAction( $action ) {
		return static::$event_manager->called( $action );
	}
}

<?php

/**
 * Provides mocking utilities for usage in testing environments,
 * extending the functionality of WP_Mock and supporting additional customization.
 */

namespace ReallySpecific\Utils;

use ReallySpecific\Utils\Testing\Constants;
use ReallySpecific\Utils\Testing\Methods;
use ReallySpecific\Utils\Testing\EventStack;

use Exception;
/**
 * Extends the functionality of the WP_Mock class to include additional mock capabilities.
 * Provides methods to bootstrap and set up mock functions and register actions.
 */
class Mock_WP {

	protected EventStack $events;

	protected Methods $methods;

	protected Constants $constants;

	protected static Mock_WP $self;

	protected array $environment = [];

	public function __construct( $args ) {
		if ( ! empty( self::$self ) ) {
			throw new Exception( 'Cannot create multiple instances of Mock_WP' );
		}
		self::$self = $this;

		if ( isset( $args['environment'] ) ) {
			$this->environment = $args['environment'];
		}

		$this->install();
	}

	public function install() {
		if ( empty( $this->methods ) ) {
			$this->methods = new Methods( $this );
		}
		if ( empty( $this->events ) ) {
			$this->events = new EventStack( $this );
			$this->methods->add_functions( $this->events->get_function_callbacks() );
		}
		if ( empty( $this->constants ) ) {
			$this->constants = new Constants( $this );
		}
	}

	public function get_test_ready() {
		$this->methods->register();
		$this->constants->register();
	}

	public function mock_function( $function_name, ...$args ) {
		return $this->methods->execute_function( $function_name, ...$args );
	}

	public static function handle_function( $function_name, ...$args ) {
		return static::$self->mock_function( $function_name, ...$args );
	}

	public function get_env( $key ) {
		return $this->environment[ $key ] ?? null;
	}

	public static function find_env_paths( $starting_dir ) {
		$this_path = rtrim( $starting_dir, '/' );
		$paths     = [];
		while ( ! file_exists( $this_path . '/wp-load.php' ) ) {
			$this_path = dirname( $this_path );
			if ( basename( $this_path ) === 'wp-content' ) {
				$paths['content_path'] = $this_path;
			}
		}
		$paths['root_path'] = $this_path;
		return $paths;
	}

	public static function setup_test_case() {
		static::$self->get_test_ready();
	}
}

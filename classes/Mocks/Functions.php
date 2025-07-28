<?php

namespace ReallySpecific\Utils\Mocks;

use ReallySpecific\Utils\Mock;

class Functions {

	protected static array $functions = [
		'did_action' => null,
	];

	public function __construct() {
		foreach ( static::$functions as $function_name => $callback ) {
			if ( is_null( $callback ) && method_exists( $this, $function_name ) ) {
				$callback = function ( ...$args ) use ( $function_name ) {
					$this->$function_name( ...$args );
				};
			}
			if ( is_callable( $callback ) ) {
				Mock::userFunction( 'did_action', [ 'return' => $callback ] );
			}
		}
	}

	public static function add_function( $function_name, $callback ) {
		static::$functions[ $function_name ] = $callback;
	}

	public function did_action( $action_name ) {
		return Mock::didAction( $action_name );
	}
}

<?php

namespace ReallySpecific\Utils\Tests\Traits;

use WP_Mock;

trait MockFunctions {

	public function mock_functions( $props = [] ) {

		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action( $props['did_action'] ?? false ) );
		WP_Mock::userFunction( 'get_plugin_data', $this->mock_get_plugin_data( $props['plugin_data'] ?? [] ) );

	}

	protected static $actions = [];

	public function do_action( $action, ...$args ) {
		self::$actions[ $action ] = [
			'args'   => $args,
			'times'  => ( self::$actions[ $action ]['times'] ?? 0 ) + 1,
		];
	}

	private function mock_sanitize_title() {
		return [
			'return' => function( $name ) {
				$slug = preg_replace( '/[^a-z0-9]/', '-', strtolower( $name ) );
				while( str_contains( $slug, '--' ) ) {
					$slug = str_replace( '--', '-', $slug );
				}
				return $slug;
			}
		];
	}


	private function mock_did_action( $did_it = null ) {
		if ( is_null( $did_it ) ) {
			$did_it = self::$actions;
		}
		return [
			'return' => function( $action ) use ( $did_it ) {
				if ( is_array( $did_it ) ) {
					return isset( $did_it[ $action ] );
				}
				return $did_it;
			}
		];
	}

	private function mock_get_plugin_data( $data ) {
		return [
			'return' => function() use ( $data ) {
				return $data;
			}
		];
	}

	private function mock_trailingslashit() {
		return [
			'return' => function( $path ) {
				return rtrim( $path, '/' ) . '/';
			}
		];
	}

}
<?php

namespace ReallySpecific\Utils\Testing;

use ReallySpecific\Utils\Mock_WP;

class Constants {

	protected Mock_WP $mock;

	private array $constants = [];

	public function __construct( Mock_WP $mock ) {
		$this->mock = $mock;
		$this->add_wp_constants();
	}

	public function register() {
		foreach ( $this->constants as $constant_name => $constant ) {
			if ( $constant['registered'] ) {
				continue;
			}
			$this->register_constant( $constant_name );
		}
	}

	private function register_constant( $constant_name ) {
		$constant_value = $this->constants[ $constant_name ]['value'] ?? null;
		if ( ! defined( $constant_name ) ) {
			define( $constant_name, $constant_value );
		}
	}

	public function add_constant( string $constant_name, $constant_value, $register = true ) {
		if ( defined( $constant_name ) ) {
			return;
		}
		$this->constants[ $constant_name ] = [
			'registered' => false,
			'value' => $constant_value
		];
		if ( $register ) {
			$this->register_constant( $constant_name );
		}
	}

	public function add_wp_constants() {
		$constants = [
			'WP_CONTENT_DIR' => $this->mock->get_env( 'content_path' ),
			'ABSPATH'        => $this->mock->get_env( 'root_path' ),
			'WPINC'          => $this->mock->get_env( 'root_path' ) . '/wp-includes',
			'EZSQL_VERSION'  => 'WP1.25',
			'OBJECT'         => 'OBJECT',
			'OBJECT_K'       => 'OBJECT_K',
			'ARRAY_A'        => 'ARRAY_A',
			'ARRAY_N'        => 'ARRAY_N',
			'WP_DEBUG'       => false,
		];
		foreach ( $constants as $constant_name => $constant_value ) {
			$this->add_constant( $constant_name, $constant_value, false );
		}
	}
}

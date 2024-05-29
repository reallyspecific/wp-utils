<?php

namespace ReallySpecific\WP_Util;

use ReallySpecific\WP_Util as Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Plugin  {

	private $root_path = null;

	private $root_file = null;

	private $services = [];

	private $i18n_domain = null;

	function __construct( array $props = [] ) {
		if ( ! empty( $props['file'] ) ) {
			$this->root_file = $props['file'];
			$this->root_path = dirname( $props['file'] );
			if ( ! empty( $props['update_host'] ) ) {
				add_filter('update_plugins_' . $props['update_host'], [ $this, 'update_check' ], 10, 4  );
			}
		}
		if ( ! empty( $props['i18n_domain'] ) ) {
			load_plugin_textdomain( $props['i18n_domain'], false, $props['i18n_path'] ?? __DIR__ . '/languages' );
			$this->i18n_domain = $props['i18n_domain'];
		}
	}

	public function __get( $name ) {
		switch( $name ) {
			case 'domain':
			case 'text_domain':
			case 'i18n_domain':
				return $this->i18n_domain;
			default:
				return null;
		}
	}

	public function attach_service( $load_action, $service_name, $callback, $callback_args = [], $admin_only = false ) {
		if ( $admin_only && ! is_admin() ) {
			return;
		}
		add_action( $load_action, function() use ( $this, $service_name, $callback, $callback_args ) {
			$this->load_service( $service_name, $callback, $callback_args );
		} );
	}

	public function load_service( $name, $callback, $callback_args = [] ) {
		if ( class_exists( $callback ) ) {
			$this->services[ $name ] = new $callback( ...$callback_args );
		} else if ( is_callable( $callback ) ) {
			$this->services[ $name ] = call_user_func_array( $callback, $callback_args );
		}
	}

	public function service( $name ) {
		return $this->services[ $name ];
	}

	public function get_root_path() {
		return $this->root_path;
	}

	public function get_root_file() {
		return $this->root_file;
	}

	public function get_url( $relative_path = null ) {
		return plugins_url( $relative_path, $this->get_root_file() );
	}

	public function update_check( $update, $plugin_data, $plugin_file ) {
		if ( $plugin_file == $this->root_file ) {
			$request      = wp_remote_get( $plugin_data['UpdateURI'] );
			$request_body = wp_remote_retrieve_body( $request );
			$update       = json_decode( $request_body, true );
		}
		return $update;
	}

}
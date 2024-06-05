<?php

namespace ReallySpecific\WP_Util;

use ReallySpecific\WP_Util as Util;
use ReallySpecific\WP_Util\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Plugin  {

	private $root_path = null;

	private $root_file = null;

	private $services = [];

	private $i18n_domain = null;

	private $name = null;

	private $settings = [];

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
		if ( ! empty( $props['name'] ) ) {
			$this->name = $props['name'];
		} else {
			$this->name = basename( $this->root_file );
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

	public function attach_service( $load_action, $service_name, $callback, $callback_args = [], $admin_only = false, $load_priority = 10 ) {
		if ( $admin_only && ! is_admin() ) {
			return;
		}
		add_action( $load_action, function() use ( $service_name, $callback, $callback_args ) {
			$this->load_service( $service_name, $callback, $callback_args );
		}, $load_priority );
	}

	public function load_service( $name, $callback, $callback_args = [] ) {
		if ( class_exists( $callback ) ) {
			$this->services[ $name ] = new $callback( $this, ...$callback_args );
		} else if ( is_callable( $callback ) ) {
			$this->services[ $name ] = call_user_func_array( $callback, [ $this ] + $callback_args );
		}
	}

	public function &service( $name ) {
		return $this->services[ $name ];
	}

	public function get_root_path() {
		return $this->root_path;
	}

	public function get_root_file() {
		return $this->root_file;
	}

	public function get_url( $relative_path = null ) {
		return untrailingslashit( plugins_url( $relative_path, $this->get_root_file() ) );
	}

	public function get_path( $relative_path = '' ) {
		return untrailingslashit( $this->get_root_path() . '/' . $relative_path );
	}

	public function update_check( $update, $plugin_data, $plugin_file ) {
		if ( $plugin_file == $this->root_file ) {
			$request      = wp_remote_get( $plugin_data['UpdateURI'] );
			$request_body = wp_remote_retrieve_body( $request );
			$update       = json_decode( $request_body, true );
		}
		return $update;
	}

	public function &settings( $namespace = 'default' ) {
		return $this->settings[ $namespace ];
	}

	public function get_setting( $namespace = 'default', $key = null ) {
		return $this->settings[ $namespace ]->get( $key );
	}

	public function add_new_settings( $namespace = 'default', string $menu_title = null, array $props = [] ) {

		class_loader( 'Settings' );

		if ( empty( $menu_title ) ) {
			$menu_title = $this->name;
		}
		$this->settings[ $namespace ] = new Settings( $this, $menu_title, $props );
	}

}
<?php

namespace ReallySpecific\WP_Util;

use ReallySpecific\WP_Util\Settings;
use ReallySpecific\WP_Util\Service_Host;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Plugin  {

	use Service_Host;

	protected $root_path = null;

	protected $root_file = null;

	protected $services = [];

	protected $i18n_domain = null;

	protected $i18n_path = null;

	protected $name = null;

	protected $settings = [];

	function __construct( array $props = [] ) {
		$props = wp_parse_args( $props, [
			'update_plugin_filter' => 'update_plugins',
		] );
		if ( ! empty( $props['file'] ) ) {
			$this->root_file = $props['file'];
			$this->root_path = dirname( $props['file'] );
			if ( ! empty( $props['update_host'] ) ) {
				add_filter( "{$props['update_plugin_filter']}_{$props['update_host']}", [ $this, 'update_check' ], 10, 4  );
			}
		}
		if ( ! empty( $props['i18n_domain'] ) ) {
			add_action( 'init', [ $this, 'install_textdomain' ] );
			$this->i18n_domain = $props['i18n_domain'];
			$this->i18n_path   = $props['i18n_path'] ?? $this->root_path . '/languages';
		}
		if ( ! empty( $props['name'] ) ) {
			$this->name = $props['name'];
		} else {
			$this->name = basename( $this->root_file );
		}
	}

	public function install_textdomain() {
		load_plugin_textdomain( $this->i18n_domain, false, $this->i18n_path );
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

	public function debug_mode() {
		return is_debug_mode();
	}

	public function update_check( $update, $item, $plugin_file ) {
		if ( $plugin_file !== $this->root_file ) {
			return $update;
		}
		
		// TODO: implement update check
	
		return $update;

	}

	public function &settings( $namespace = 'default' ) {
		return $this->settings[ $namespace ];
	}

	public function get_setting( $namespace = 'default', $key = null ) {
		$settings = $this->settings[ $namespace ] ?? null;
		if ( empty( $settings ) ) {
			return null;
		}
		return $settings->get( $key );
	}

	public function add_new_settings( $namespace = 'default', string $menu_title = null, array $props = [] ) {

		class_loader( 'Settings' );

		if ( empty( $menu_title ) ) {
			$menu_title = $this->name;
		}
		$this->settings[ $namespace ] = new Settings( $this, $menu_title, $props );
	}

}
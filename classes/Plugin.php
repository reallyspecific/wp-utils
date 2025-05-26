<?php

namespace ReallySpecific\WP_Util;

use ReallySpecific\WP_Util\Settings;
use ReallySpecific\WP_Util\Service_Host;
use ReallySpecific\WP_Util\Updatable;

class Plugin {

	use Service_Host, Updatable;

	protected $root_path = null;

	protected $root_file = null;

	protected $services = [];

	protected $i18n_domain = null;

	protected $i18n_path = null;

	protected $name = null;

	protected $slug = null;

	protected $settings = [];

	protected $data = [];

	function __construct( array $props = [] ) {
		if ( ! empty( $props['file'] ) ) {
			$this->root_file = $props['file'];
			$this->root_path = dirname( $props['file'] );
			$this->data      = $this->load_wp_data();
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
		$this->slug = $props['slug'] ?? sanitize_title( $this->name );
		if ( ! empty( $this->get_wp_data( 'UpdateURI' ) ) ) {
			$this->install_updater();
		}
	}

	protected function load_wp_data() {
		$plugin = get_plugin_data( $this->root_file );
		$this->data = $plugin;
		return $plugin;
	}

	public function get_wp_data( $key = null ) {
		if ( empty( $key ) ) {
			return $this->data;
		}
		return $this->data[ $key ] ?? null;
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
			case 'slug':
				return $this->slug;
			case 'name':
				return $this->name;
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

		if ( empty( $menu_title ) ) {
			$menu_title = $this->name;
		}
		$this->settings[ $namespace ] = new Settings( $this, $menu_title, $props );
	}

}
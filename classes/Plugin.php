<?php

namespace ReallySpecific\Utils;

use ReallySpecific\Utils\Settings;
use ReallySpecific\Utils\Service_Host;
use ReallySpecific\Utils\Updatable;

abstract class Plugin {

	use Service_Host;

	protected $root_path = null;

	protected $root_file = null;

	protected $services = [];

	protected $i18n_domain = null;

	protected $i18n_path = null;

	protected $name = null;

	protected $slug = null;

	protected $settings = [];

	protected $data = [];

	protected $updater = null;

	/**
	 * Plugin constructor.
	 *
	 * @param array $props
	 * @throws \Exception
	 */
	function __construct( array $props = [] ) {

		if ( empty( $props['name'] ) ) {
			throw new \Exception( 'Plugin was constructed without a `name` property.' );
		}

		$this->root_file = $props['file'] ?? $this->get_root_file();
		$this->root_path = dirname( $this->root_file );

		$this->i18n_domain = $props['i18n_domain'] ?? null;
		$this->i18n_path   = $props['i18n_path'] ?? $this->root_path . '/languages';

		$this->name = $props['name'];
		$this->slug = $props['slug'] ?? sanitize_title( basename( $this->root_path ) );

		add_action( 'init', [ $this, 'get_wp_data' ] );
		add_action( 'init', [ $this, 'setup_updater' ] );
		add_action( 'init', [ $this, 'install_settings' ] );
		add_action( 'init', [ $this, 'install_textdomain' ] );
	}

	protected static $self = null;

	/**
	 * Returns a statically stored instance of the plugin. Generally
	 * it should only be used by classes that extend the Plugin class,
	 * as otherwise all Plugins will end up referencing the same object.
	 * @return Plugin
	 */
	public static function instance() {
		if ( static::$self ) {
			return static::$self;
		}
		return static::setup();
	}

	/**
	 * Sets up the static instance of the plugin. Basically useless 
	 * unless overloaded.
	 * @return Plugin
	 */
	protected static function setup() {
		static::$self = new static();
		return static::$self;
	}

	public function setup_updater() {
		if ( empty( $this->get_wp_data( 'UpdateURI' ) ) ) {
			return;
		}
		$this->updater = new Updater( [
			'object'     => $this,
			'update_uri' => $this->get_wp_data( 'UpdateURI' ),
			'slug'       => $this->slug,
			'file'       => $this->root_file,
		] );
	}

	protected function load_wp_data() {
		if ( ! function_exists( '\get_plugin_data' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin = \get_plugin_data( $this->root_file );
		$this->data = $plugin;
		return $plugin;
	}

	public function get_wp_data( $key = null ) {
		if ( empty( $this->data ) && did_action( 'init' ) ) {
			$this->load_wp_data();
		}
		if ( empty( $key ) ) {
			return $this->data;
		}
		return $this->data[ $key ] ?? null;
	}

	public function install_textdomain() {
		if ( ! empty( $this->domain ) ) {
			load_plugin_textdomain( $this->domain, false, $this->i18n_path );
		}
	}

	public function __get( $key ) {
		switch( $key ) {
			case 'name':
				return $this->get_name();
			case 'domain':
			case 'text_domain':
			case 'i18n_domain':
				return $this->get_domain();
			case 'slug':
				return $this->slug;
			case 'file':
				return $this->get_root_file();
			case 'path':
				return $this->get_root_path();
			case 'update_uri':
				return $this->get_wp_data( 'UpdateURI' );
			default:
				return null;
		}
	}

	public function get_domain() {
		if ( empty( $this->i18n_domain ) && did_action('init') ) {
			$this->i18n_domain = $this->get_wp_data('TextDomain');
		}
		return $this->i18n_domain ?? null;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_root_path() {
		return $this->root_path;
	}

	public function get_root_file() {
		if ( is_null( $this->root_file ) ) {
			return trailingslashit( \WP_PLUGIN_DIR ) . plugin_basename( __FILE__ );
		}
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
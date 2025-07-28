<?php

namespace ReallySpecific\Utils;

abstract class Theme extends Plugin {

	function __construct( array $props = [] ) {

		parent::__construct( [ 'update_plugin_filter' => 'update_themes', ...$props ] );

		if ( isset( $props['env'] ) ) {
			$this->env = $props['env'];
		}

		//add_action( 'wp_head', [ $this, 'install_environment_variables' ] );

        add_action( 'after_setup_theme', [ $this, 'setup' ] );
	}

	public function get_root_file() {
		if ( is_null( $this->root_file ) ) {
			return get_stylesheet_directory() . '/style.css';
		}
		return $this->root_file;
	}

	protected function load_wp_data() {
		$theme = wp_get_theme( basename( $this->root_path ) );
		$this->data = $theme;
		return $theme;
	}

	public function get_wp_data( $key = null ) {
        $all_data = parent::get_wp_data();
		if ( empty( $key ) ) {
			return $all_data;
		}
		return $all_data->get( $key ) ?? null;
	}



	public function install_textdomain() {
		load_theme_textdomain( $this->i18n_domain, false, $this->i18n_path );
	}

	public function __get( $key ) {
		switch( $key ) {
			case 'version':
				return $this->get_version();
			default:
				return parent::__get( $key );
		}
	}

	public function get_version() {
		$version = wp_cache_get( 'version', $this->name );
		if ( ! $version ) {
			$version = include get_theme_file_path( 'assets/dist/version.php' );
			if ( empty( $version ) ) {
				$version = wp_get_theme()->get( 'Version' );
			}
			wp_cache_set( 'version', $version, $this->name );
		}
		return $version;
	}



	public function get_url( $relative_path = null ) {
		return trailingslashit( get_theme_root_uri( $this->get_root_file() ) ) . ltrim( $relative_path, '/' );
	}

}
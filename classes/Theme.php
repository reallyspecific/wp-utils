<?php

namespace ReallySpecific\WP_Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Theme extends Plugin {

	function __construct( array $props = [] ) {
		parent::__construct( [ 'update_plugin_filter' => 'update_themes', ...$props ] );
	}

	public function install_textdomain() {
		load_theme_textdomain( $this->i18n_domain, false, $this->i18n_path );
	}

	public function __get( $name ) {
		switch( $name ) {
			case 'version':
				return $this->get_version();
			default:
				return parent::__get( $name );
		}
	}

	public function get_version() {
		$version = wp_cache_get( 'version', $this->name );
		if ( ! $version ) {
			$version = include get_parent_theme_file_path( 'assets/dist/version.php' );
			if ( empty( $version ) ) {
				$version = wp_get_theme()->get( 'Version' );
			}
			wp_cache_set( 'version', $version, $this->name );
		}
		return $version;
	}

}
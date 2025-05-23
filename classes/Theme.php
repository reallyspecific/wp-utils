<?php

namespace ReallySpecific\WP_Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Theme extends Plugin {

	function __construct( array $props = [] ) {
		if ( ! empty( $props['file'] ) ) {
			$this->root_file = $props['file'];
			$this->root_path = dirname( $props['file'] );
			if ( ! empty( $props['update_host'] ) ) {
				add_filter( 'update_themes_' . $props['update_host'], [ $this, 'update_check' ], 10, 4  );
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
		load_theme_textdomain( $this->i18n_domain, false, $this->i18n_path );
	}

}
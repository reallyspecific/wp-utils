<?php

namespace ReallySpecific\Utils\Tests\Traits;

use function ReallySpecific\Utils\Tests\config;
use WP_Mock;

trait MockFunctions {

	public function mock_functions( $props = [] ) {

		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action( $props['did_action'] ?? false ) );
		WP_Mock::userFunction( 'get_plugin_data', $this->mock_get_plugin_data( $props['plugin_data'] ?? [] ) );
		WP_Mock::userFunction( 'plugins_url', $this->mock_plugins_url() );
		WP_Mock::userFunction( 'set_url_scheme', $this->mock_set_url_scheme() );
		WP_Mock::userFunction( 'is_ssl', $this->mock_is_ssl() );
		WP_Mock::userFunction( 'force_ssl_admin', $this->mock_force_ssl_admin() );
		WP_Mock::userFunction( 'plugin_basename', $this->mock_plugin_basename() );
		WP_Mock::userFunction( 'wp_normalize_path', $this->mock_wp_normalize_path() );
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

	public function trailingslashit( $path ) {
		return rtrim( $path, '/' ) . '/';
	}

	private function mock_trailingslashit() {
		return [
			'return' => fn( $path ) => $this->trailingslashit( $path )
		];
	}

	public function untrailingslashit( $path ) {
		return rtrim( $path, '/' );
	}

	private function mock_untrailingslashit() {
		return [
			'return' => fn( $path ) => $this->untrailingslashit( $path )
		];
	}

	public function wp_normalize_path( $path ) {
		$wrapper = '';
	
		/*
		if ( wp_is_stream( $path ) ) {
			list( $wrapper, $path ) = explode( '://', $path, 2 );
	
			$wrapper .= '://';
		}
		*/
	
		// Standardize all paths to use '/'.
		$path = str_replace( '\\', '/', $path );
	
		// Replace multiple slashes down to a singular, allowing for network shares having two slashes.
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
	
		// Windows paths should uppercase the drive letter.
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
	
		return $wrapper . $path;
	}

	private function mock_wp_normalize_path() {
		return [
			'return' => fn( $path ) => $this->wp_normalize_path( $path )
		];
	}

	public function plugin_basename( $file ) {
		
		$wp_plugin_paths = [
			'wp-content/plugins' => config( 'plugins_dir' ),
			'wp-content/mu-plugins' => config( 'mu_plugins_dir' ),
		];
	
		// $wp_plugin_paths contains normalized paths.
		$file = $this->wp_normalize_path( $file );
	
		arsort( $wp_plugin_paths );
	
		foreach ( $wp_plugin_paths as $dir => $realdir ) {
			if ( str_starts_with( $file, $realdir ) ) {
				$file = $dir . substr( $file, strlen( $realdir ) );
			}
		}
	
		$plugin_dir    = $this->wp_normalize_path( 'wp-content/plugins' );
		$mu_plugin_dir = $this->wp_normalize_path( 'wp-content/mu-plugins' );
	
		// Get relative path from plugins directory.
		$file = str_replace( $plugin_dir, '', $file );
		$file = str_replace( $mu_plugin_dir, '', $file );
		$file = trim( $file, '/' );
		return $file;
	}

	private function mock_plugin_basename() {
		return [
			'return' => fn( $file ) => $this->plugin_basename( $file )
		];
	}

	public function plugins_url( $path = '', $plugin = '' ) {

		$path          = $this->wp_normalize_path( $path );
		$plugin        = $this->wp_normalize_path( $plugin );
		$mu_plugin_dir = $this->wp_normalize_path( config( 'mock_mu_plugin_path' ) );
	
		if ( ! empty( $plugin ) && str_starts_with( $plugin, $mu_plugin_dir ) ) {
			$url = config( 'mock_mu_plugin_url' );
		} else {
			$url = config( 'mock_plugin_url' );
		}
	
		$url = $this->set_url_scheme( $url );
	
		if ( ! empty( $plugin ) && is_string( $plugin ) ) {
			$folder = dirname( $this->plugin_basename( $plugin ) );
			if ( '.' !== $folder ) {
				$url .= '/' . ltrim( $folder, '/' );
			}
		}
	
		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}
	
		return $url;
	}

	private function mock_plugins_url() {
		return [
			'return' => fn( $path = '', $plugin = '' ) => $this->plugins_url( $path, $plugin )
		];
	}

	public function set_url_scheme( $url, $scheme = null ) {
		$orig_scheme = $scheme;
	
		if ( ! $scheme ) {
			$scheme = $this->is_ssl() ? 'https' : 'http';
		} elseif ( 'admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme ) {
			$scheme = $this->is_ssl() || $this->force_ssl_admin() ? 'https' : 'http';
		} elseif ( 'http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme ) {
			$scheme = $this->is_ssl() ? 'https' : 'http';
		}
	
		$url = trim( $url );
		if ( str_starts_with( $url, '//' ) ) {
			$url = 'http:' . $url;
		}
	
		if ( 'relative' === $scheme ) {
			$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
			if ( '' !== $url && '/' === $url[0] ) {
				$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
			}
		} else {
			$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
		}
	
		return $url;
	}

	private function mock_set_url_scheme() {
		return [
			'return' => fn( $url, $scheme = null ) => $this->set_url_scheme( $url, $scheme )
		];
	}

	public function is_ssl() {
		return true;

		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( 'on' === strtolower( $_SERVER['HTTPS'] ) ) {
				return true;
			}
	
			if ( '1' === (string) $_SERVER['HTTPS'] ) {
				return true;
			}
		} elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === (string) $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
	
		return false;
	}

	private function mock_is_ssl() {
		return [
			'return' => fn() => $this->is_ssl()
		];
	}
	
	public function force_ssl_admin( $force = null ) {
		static $forced = false;
	
		if ( ! is_null( $force ) ) {
			$old_forced = $forced;
			$forced     = (bool) $force;
			return $old_forced;
		}
	
		return $forced;
	}
	
	private function mock_force_ssl_admin() {
		return [
			'return' => fn( $force = null ) => $this->force_ssl_admin( $force )
		];
	}
	

}
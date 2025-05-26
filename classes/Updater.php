<?php

namespace ReallySpecific\WP_Util;

class Updater {

	protected $update_uri;
	protected $update_token;
	protected $update_host;
	protected $type;
	protected $basename;

	protected $source_path;

	public function __construct( $props = [] ) {

		$this->update_uri = $props['update_uri'] ?? null;

		if ( ! empty( $props['object'] ) ) {
			$props['type'] ??= $props['object'] instanceof Theme ? 'theme' : 'plugin';
		}

		$this->type = $props['type'];

		if ( ! empty( $this->update_uri ) ) {
			$this->update_host  = parse_url( $this->update_uri, PHP_URL_HOST );
			$update_slug = $props['slug'] ?? sanitize_title( $this->update_host );
			$this->update_token = apply_filters( "rs_util_updater_update_token_{$update_slug}", $props['update_token'] ?? null, $this );

			if ( $props['type'] === 'theme' ) {
				add_filter( "update_themes_{$this->update_host}", [ $this, 'check_theme' ], 10, 4);
			} else {
				add_filter( "update_plugins_{$this->update_host}", [ $this, 'check_plugin' ], 10, 3);
			}
			$updater_actions = __DIR__ . '/updaters/' . sanitize_title( $this->update_host ) . '.php';
			if ( file_exists( $updater_actions ) ) {
				include_once $updater_actions;
			}
		}

		$this->source_path = dirname( $props['file'] );

		if ( $props['type'] === 'theme' ) {
			$this->basename = 'style.css';
		} else {
			$this->basename = basename( $props['file'] );
		}

	}

	public function __get( $name ) {
		switch( $name ) {
			case 'uri':
				return $this->update_uri;
			case 'host':
				return $this->update_host;
			case 'token':
				return $this->update_token;
			case 'type':
				return $this->type;
			case 'basename':
				return $this->basename;
			default:
				return null;
		}
	}

	protected static function get_package_version( $release ) {
		return $release['Version'];
	}

	protected static function parse_release( $package ) {
		return [
			'theme'        => $package['name'],
			'url'          => $package['url'],
			'tested'       => $package['published_at'],
			'requires_php' => $package['php'],
			'version'      => static::get_package_version( $package ),
			'package'      => $package['browser_download_url'],
		];
	}

	public function check_plugin( $update, $item, $plugin_file ) {

		return $update;
	}

	protected function get_package_info( $props ) {

		$package_uri = $props['update_uri'];

		$request_headers = [];
		if ( ! empty( $this->update_token ) ) {
			$request_headers['Authorization'] = 'Bearer ' . $this->update_token;
		}

		$package_retrieval_uri     = apply_filters( 'rs_util_updater_package_retrieval_uri_' . $this->update_host, $props, $this );
		$package_retrieval_params = apply_filters( 'rs_util_updater_package_retrieval_params_' . $this->update_host, [
			'headers' => $request_headers,
		], $this );

		$request  = wp_remote_get( $package_retrieval_uri, $package_retrieval_params );
		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
			// todo: log this error somehow
			return false;
		}

		$headers  = wp_remote_retrieve_headers( $request );
		$response = wp_remote_retrieve_body( $request );

		if ( is_string( $response ) && str_contains( $headers['Content-Type'], 'application/json' ) ) {
			$response = json_decode( $response, \true );
		}

		$package = apply_filters( 'rs_util_updater_package_body_' . $this->update_host, $response, $props, $this );

		if ( is_string( $response ) && str_contains( $headers['Content-Type'], 'text/css' ) ) {
			$metafile = wp_tempnam( $props['basename'] );
			file_put_contents( $metafile, $response );
			$package = get_file_data( $metafile, $props['current'] );
			unlink( $metafile );
		}

		$package = apply_filters( 'rs_util_updater_package_info_' . $this->update_host, $package, $this );

		return $package;
	}

	public function check_theme( $update, $item, $data, $context ) {

		$request_uri      = apply_filters( 'rs_util_updater_theme_update_uri_' . $this->update_host, $this->update_uri, $this );
		$package_basename = apply_filters( 'rs_util_updater_theme_package_basename_' . $this->update_host, $this->basename, $this );

		$package = $this->get_package_info( [
			'update_uri' => $request_uri,
			'basename'   => $package_basename,
			'current'    => $item,
		] );
		if ( empty( $package ) ) {
			return $update;
		}

		$version = static::get_package_version( $package );
		if ( version_compare( $version, $item['version'], '>' ) ) {
			$update = static::parse_release( $package );
		}

		return $update;
	}

}

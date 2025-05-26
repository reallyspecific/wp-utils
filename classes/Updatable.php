<?php

namespace ReallySpecific\WP_Util;

trait Updatable {

	protected $update_uri;
	protected $update_token;
	protected $update_host;
	protected $type;

	public function install_updater( $update_token = null ) {
		$this->update_uri = $this->get_wp_data( 'UpdateURI' );
		if ( ! empty( $this->update_uri ) ) {
			$this->update_host  = parse_url( $this->update_uri, PHP_URL_HOST );
			$this->update_token = apply_filters( "rs_util_updater_update_token_{$this->slug}", $update_token, $this );

			if ( $this instanceof Theme ) {
				$this->type = 'theme';
				add_filter( "update_themes_{$this->update_host}", [ $this, 'check_theme' ], 10, 4);
			} else {
				$this->type = 'plugin';
				add_filter( "update_plugins_{$this->update_host}", [ $this, 'check_plugin' ], 10, 3);
			}
			$updater_actions = __DIR__ . '/updaters/' . sanitize_title( $this->update_host ) . '.php';
			if ( file_exists( $updater_actions ) ) {
				include_once $updater_actions;
			}
		}

	}

	protected function get_package_version( $release ) {
		return $release['Version'];
	}

	protected function parse_release( $package ) {
		return [
			'theme'        => $package['name'],
			'url'          => $package['url'],
			'tested'       => $package['published_at'],
			'requires_php' => $package['php'],
			'version'      => $this->get_package_version( $package ),
			'package'      => $package['browser_download_url'],
		];
	}

	public function check_plugin( $update, $item, $plugin_file ) {

		return $update;
	}

	protected function get_package_info( $package_uri, $package_filename ) {

		$request_headers = [];
		if ( ! empty( $this->update_token ) ) {
			$request_headers['Authorization'] = 'Bearer ' . $this->update_token;
		}

		$package_retrieval_uri     = apply_filters( 'rs_util_updater_package_retrieval_uri_' . $this->update_host, $package_uri, $this );
		$package_retrieval_headers = apply_filters( 'rs_util_updater_package_retrieval_headers_' . $this->update_host, [
			'headers' => $request_headers,
		], $this );

		$request  = wp_remote_get( $package_retrieval_uri, $package_retrieval_headers );
		if ( is_wp_error( $request ) ) {
			// todo: log this error somehow
			return false;
		}

		$headers = wp_remote_retrieve_headers( $request );
		$response = wp_remote_retrieve_body( $request );

		$response = apply_filters( 'rs_util_updater_package_body_' . $this->update_host, $response, $this );

		if ( is_string( $response ) && str_contains( $headers['Content-Type'], 'application/json' ) ) {
			$package = json_decode( $response, \true );
		}

		if ( is_string( $response ) && str_contains( $headers['Content-Type'], 'text/css' ) ) {
			$metafile = wp_tempnam( $package_filename );
			file_put_contents( $metafile, $response );
			$package = get_file_data( $metafile, [] );
			unlink( $metafile );
		}

		$package = apply_filters( 'rs_util_updater_package_info_' . $this->update_host, $package, $this );

		return $package;
	}

	public function check_theme( $update, $item, $data, $context ) {

		$request_uri      = apply_filters( 'rs_util_updater_theme_update_uri_' . $this->update_host, $this->update_uri, $this );
		$package_basename = apply_filters( 'rs_util_updater_theme_package_basename_' . $this->update_host, basename( $this->root_file ), $this );

		$package = $this->get_package_info( $request_uri, $package_basename );
		if ( empty( $package ) ) {
			return $update;
		}

		$version = $this->get_package_version( $package );
		if ( version_compare( $version, $item['version'], '>' ) ) {
			$update = $this->parse_release( $package );
		}

		return $update;
	}

}

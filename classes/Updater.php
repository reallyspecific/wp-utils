<?php

namespace ReallySpecific\WP_Util;

class Updater {

	protected $update_uri;
	protected $update_token;
	protected $update_host;
	protected $type;

	protected $slug;
	protected $basename;

	protected $source_path;

	public static $default_headers = [
		'theme' => [
			'Name'            => 'Theme Name',
			'ThemeURI'        => 'Theme URI',
			'Author'          => 'Author',
			'AuthorURI'       => 'Author URI',
			'Version'         => 'Version',
			'License'         => 'License',
			'LicenseURI'      => 'License URI',
			'TextDomain'      => 'Text Domain',
			'DomainPath'      => 'Domain Path',
			'Template'        => 'Template',
			'TemplateVersion' => 'Template Version',
			'Network'         => 'Network',
			'TestedWP'        => 'Tested up to',
			'RequiresWP'      => 'Requires at least',
			'RequiresPHP'     => 'Requires PHP',
			'UpdateURI'       => 'Update URI',
			'DownloadZipURI'  => 'Download URL',
		],
		'plugin' => [
			'Name'            => 'Plugin Name',
			'PluginURI'       => 'Plugin URI',
			'Version'         => 'Version',
			'Description'     => 'Description',
			'Author'          => 'Author',
			'AuthorURI'       => 'Author URI',
			'TextDomain'      => 'Text Domain',
			'DomainPath'      => 'Domain Path',
			'Network'         => 'Network',
			'TestedWP'        => 'Tested up to',
			'RequiresWP'      => 'Requires at least',
			'RequiresPHP'     => 'Requires PHP',
			'UpdateURI'       => 'Update URI',
			'RequiresPlugins' => 'Requires Plugins',
			'DownloadZipURI'  => 'Download URL',
			'_sitewide'       => 'Site Wide Only',
	]
	];

	public function __construct( $props = [] ) {

		$this->update_uri = $props['update_uri'] ?? null;

		if ( ! empty( $props['object'] ) ) {
			$props['type'] ??= $props['object'] instanceof Theme ? 'theme' : 'plugin';
		}

		$this->type = $props['type'];
		$this->slug = $props['slug'] ?? basename( $this->update_uri );

		$this->source_path = dirname( $props['file'] );

		if ( $props['type'] === 'theme' ) {
			$this->basename = 'style.css';
		} else {
			$this->basename = basename( $props['file'] );
		}

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

		if ( ! has_filter( 'upgrader_pre_download', [ static::class, 'pre_download_authenticated_package' ] ) ) {
			add_filter( 'upgrader_pre_download', [ static::class, 'pre_download_authenticated_package' ], 10, 3 );
		}
		if ( ! has_filter( 'upgrader_install_package_result', [ static::class, 'cleanup_tmp_download' ] ) ) {
			add_filter( 'upgrader_install_package_result', [ static::class, 'cleanup_tmp_download' ], 10, 1 );
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

		$package_retrieval_uri     = apply_filters( 'rs_util_updater_package_retrieval_uri_' . $this->update_host, $package_uri, $props, $this );
		$package_retrieval_params = apply_filters( 'rs_util_updater_package_retrieval_params_' . $this->update_host, [
			'headers' => $request_headers,
		], $props, $this );

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

		$package = apply_filters( 'rs_util_updater_package_body_' . $this->update_host, $response, $this );

		if ( is_string( $package ) ) {
			$metafile = wp_tempnam( $props['basename'] );
			file_put_contents( $metafile, $package );
			$package = get_file_data( $metafile, static::$default_headers[ $this->type ] );
			unlink( $metafile );
		}

		$package = apply_filters( 'rs_util_updater_package_info_' . $this->update_host, $package, $response, $this );

		return $package;
	}

	public function check_theme( $update, $item, $data, $context ) {

		if ( $this->slug !== $data ) {
			return $update;
		}

		$request_uri      = apply_filters( 'rs_util_updater_theme_update_uri_' . $this->update_host, $this->update_uri, $this );
		$package_basename = apply_filters( 'rs_util_updater_theme_package_basename_' . $this->update_host, $this->basename, $this );

		$package = $this->get_package_info( [
			'update_uri' => $request_uri,
			'basename'   => $package_basename,
			'current'    => $item,
		] );
		if ( empty( $package ) || empty( $package['Version'] ) || empty( $package['DownloadZipURI'] ) ) {
			return $update;
		}

		if ( version_compare( $package['Version'], $item['Version'], '>' ) ) {
			$update = apply_filters( 'rs_util_updater_theme_update_' . $this->update_host, [
				'id'           => $item['UpdateURI'],
				'slug'         => $data,
				'theme'        => $data,
				'version'      => $package['Version'],
				'url'          => $package['ThemeURI'],
				'tested'       => $package['TestedWP'],
				'requires_php' => $package['RequiresPHP'],
				'autoupdate'   => true,
				'package'      => $package['DownloadZipURI'],
				'token'        => $this->update_token,
			], $package, $item, $data, $context );

			set_transient( 'rs_util_updater_' . $update['package'], $update, HOUR_IN_SECONDS );
		}

		return $update;
	}

	public static function pre_download_authenticated_package( $reply, $package_url, $upgrader ) {

		$package = get_transient( 'rs_util_updater_' . $package_url );
		if ( empty( $package ) || empty( $package['token'] ) ) {
			return $reply;
		}

		$options = [ 'http' => [
			'method'  => 'GET',
			'header' => 'Authorization: Bearer ' . $package['token'],
		] ];

		$host = parse_url( $package['id'], PHP_URL_HOST );

		$options = apply_filters( "rs_util_updater_authenticated_package_options_{$host}", $options, $package_url, $package );

		$context  = stream_context_create($options);
		$download = file_get_contents( $package_url, false, $context );
		if ( ! $download || is_wp_error( $download ) ) {
			return $reply;
		}

		$tmp_file = wp_tempnam( $package['basename'] );
		file_put_contents( $tmp_file, $download );

		delete_transient( 'rs_util_updater_' . $package_url );

		return $tmp_file;
	}

	public static function cleanup_tmp_download( $result ) {
		
		return $result;
	}

}

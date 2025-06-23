<?php

namespace ReallySpecific\Utils\Tests;

use function ReallySpecific\Utils\Filesystem\make_zip_from_folder;
use Symfony\Component\Finder\Finder;

function config( $key ) {
	switch( $key ) {
		case 'root_dir':
			return dirname( __DIR__ );
		case 'tests_dir':
			return __DIR__;
		case 'cache_dir':
			return __DIR__ . '/.cache';
		case 'plugins_dir':
			return __DIR__ . '/bootstrap/wp-content/plugins';
		case 'mu_plugins_dir':
			return __DIR__ . '/bootstrap/wp-content/mu-plugins';
		case 'sample_plugin_dir':
			return config( 'plugins_dir' ) . '/sample-plugin';
		case 'sample_plugin_url':
			return 'https://example.com/wp-content/plugins/sample-plugin';
		case 'mock_plugin_url':
			return 'https://example.com/wp-content/plugins';
		case 'mock_mu_plugin_url':
			return 'https://example.com/wp-content/mu-plugins';
		case 'mock_plugin_path':
			return __DIR__ . '/bootstrap/wp-content/plugins';
		case 'mock_mu_plugin_path':
			return __DIR__ . '/bootstrap/wp-content/mu-plugins';
		default:
			return null;
	}
}

function build_sample_plugin( $hash_cache_file = null ) {

	if ( is_null( $hash_cache_file ) ) {
		$hash_cache_file = config( 'cache_dir' ) . '/util-source-hash.php';
	}

	if ( ! file_exists( $hash_cache_file ) ) {
		echo 'Scoper hash not found, please run `composer scope-sample-plugin` first.';
		exit;
	}

	$old_hash = include $hash_cache_file;

	$finder = Finder::class;
	$config = include __DIR__ . '/sample-plugin.scoper.inc.php';

	$current_hash = make_hash_from_finders( $config['finders'] );

	if ( $old_hash !== $current_hash ) {
		echo 'Sample plugin does not have up-to-date files, please run `composer scope-sample-plugin`';
		exit;
	}

	if ( ! make_zip_from_folder( config( 'sample_plugin_dir' ), config( 'cache_dir' ) . '/sample-plugin.zip' ) ) {
		echo 'Failed to build sample plugin zip';
		exit;
	}

	file_put_contents( $hash_cache_file, $current_hash );

}

function make_hash_from_finders( array $finders ) {
	$hashed = '';
	foreach( $finders as $finder ) {
		foreach( $finder as $file ) {
			// hash file contents
			$hashed .= md5_file( $file );
		}
	}
	return $hashed;
}
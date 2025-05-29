<?php

namespace ReallySpecific\Utils\Tests\Bootstrap;

use Symfony\Component\Finder\Finder;

use function ReallySpecific\Utils\Filesystem\make_hash_from_finders;
use function ReallySpecific\Utils\Filesystem\make_zip_from_folder;
use function ReallySpecific\Utils\Tests\config;

function build_sample_plugin() {

	if ( ! file_exists( __DIR__ . '/.cache/sample-plugin-hash.php' ) ) {
		echo 'Scoper hash not found, please run `composer scope-sample-plugin` first.';
		exit;
	}

	$old_hash = include __DIR__ . '/.cache/sample-plugin-hash.php';

	$current_hash = make_hash_from_finders( [
		Finder::create()->files()->in( config( 'root_dir' ) . '/assets' ),
		Finder::create()->files()->in( config( 'root_dir' ) )->exclude(['tests','vendor','vendor-bin'])->name( [ '*.php' ] ),
	] );

	if ( $old_hash !== $current_hash ) {
		echo 'Sample plugin does not have up-to-date files, please run `composer scope-sample-plugin`';
		exit;
	}

	if ( ! make_zip_from_folder( __DIR__ . '/sample-plugin', __DIR__ . '/.cache/sample-plugin.zip' ) ) {
		echo 'Failed to build sample plugin zip';
		exit;
	}

	file_put_contents( __DIR__ . '/.cache/sample-plugin-hash.php', $current_hash );

}
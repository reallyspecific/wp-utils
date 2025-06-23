<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../functions/filesystem.php';

use function ReallySpecific\Utils\Tests\config;
use function ReallySpecific\Utils\Tests\make_hash_from_finders;
use function ReallySpecific\Utils\Filesystem\recursive_mk_dir;
use function ReallySpecific\Utils\Filesystem\recursive_rm_dir;
use Symfony\Component\Finder\Finder;

$project_source_dir = config( 'root_dir' );

/*
 * First we need to copy the relevant files into 'vendor' so Scoper can find them.
 */
$finders = [
	Finder::create()->files()->ignoreVCS(true)->exclude( [ 'node_modules', 'vendor', 'tests', '.cache' ] )->in( config( 'root_dir' ) )->name( [ '*.php', '*.css', '*.js', '*.csv', '*.svg' ] ),
];

if ( is_dir( config( 'root_dir' ) . '/vendor/reallyspecific' ) ) {
	recursive_rm_dir( config( 'root_dir' ) . '/vendor/reallyspecific' );
}
if ( ! is_dir( config( 'root_dir' ) . '/vendor' ) ) {
	die( 'Please run `composer install` first.' );
}
foreach( $finders as $finder ) {
	foreach( $finder as $file ) {
		$file_path = config( 'root_dir' ) . '/vendor/reallyspecific/wp-utils/' . $file->getRelativePath();
		recursive_mk_dir( $file_path );
		$file_to_copy = $file->getRealPath();
		copy( $file_to_copy, $file_path . '/' . $file->getFilename() );
	}
}

$scoper_config = include $project_source_dir . '/assets/scoper-config.inc.php';
$scoper_config['output-dir'] = config( 'sample_plugin_dir' ) . '/dependencies';
$scoper_config['prefix'] = 'ReallySpecific\\SamplePlugin\\Dependencies';

$hashed = make_hash_from_finders( $scoper_config['finders'] );
if ( ! file_exists( __DIR__ . '/.cache' ) ) {
	mkdir( __DIR__ . '/.cache' );
}
file_put_contents( __DIR__ . '/.cache/util-source-hash.php', sprintf(
	"<?php return '%s';", $hashed
) );

return $scoper_config;
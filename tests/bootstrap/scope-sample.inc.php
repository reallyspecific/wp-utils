<?php

require_once __DIR__ . '/../../load.php';

use function ReallySpecific\Utils\utils_dir;
use function ReallySpecific\Utils\autoload_directory;
use function ReallySpecific\Utils\Filesystem\make_hash_from_finders;

autoload_directory( utils_dir() . '/functions' );

$project_source_dir = utils_dir();
$util_source_dir = utils_dir();

$scoper_config = include $project_source_dir . '/assets/scoper-config.inc.php';
$scoper_config['output-dir'] = __DIR__ . '/sample-plugin/inc/utils';
$scoper_config['prefix'] = 'ReallySpecific\\SamplePlugin';

$hashed = make_hash_from_finders( $scoper_config['finders'] );
if ( ! file_exists( __DIR__ . '/.cache' ) ) {
	mkdir( __DIR__ . '/.cache' );
}
file_put_contents( __DIR__ . '/.cache/sample-plugin-hash.php', sprintf( 
	"<?php return '%s';", $hashed
) );

return $scoper_config;
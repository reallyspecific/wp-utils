<?php

require_once __DIR__ . '/../config.php';

use function ReallySpecific\Utils\Tests\config;
use function ReallySpecific\Utils\Tests\make_hash_from_finders;

$project_source_dir = config( 'root_dir' );
$util_source_dir = config( 'root_dir' );

$scoper_config = include $project_source_dir . '/assets/scoper-config.inc.php';
$scoper_config['output-dir'] = __DIR__ . '/sample-plugin/inc/utils';
$scoper_config['prefix'] = 'ReallySpecific\\SamplePlugin';

$hashed = make_hash_from_finders( $scoper_config['finders'] );
if ( ! file_exists( __DIR__ . '/../.cache' ) ) {
	mkdir( __DIR__ . '/../.cache' );
}
file_put_contents( __DIR__ . '/../.cache/util-source-hash.php', sprintf( 
	"<?php return '%s';", $hashed
) );

return $scoper_config;
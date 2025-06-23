<?php

namespace ReallySpecific\Utils\Tests;

use function ReallySpecific\Utils\autoload_directory;
use WP_Mock;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../load.php';
require_once __DIR__ . '/config.php';

autoload_directory( __DIR__ . '/traits' );

build_sample_plugin();

require_once config( 'sample_plugin_dir' ) . '/dependencies/reallyspecific/wp-utils/load.php';
\ReallySpecific\SamplePlugin\Dependencies\RS_Utils\setup();
WP_Mock::bootstrap();

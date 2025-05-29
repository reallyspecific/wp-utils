<?php

namespace ReallySpecific\Utils\Tests;

use function ReallySpecific\Utils\setup as setup_utils;
use function ReallySpecific\Utils\autoload_directory;
use WP_Mock;

require_once __DIR__ . '/config.php';

require_once config( 'root_dir' ) . '/vendor/autoload.php';
require_once config( 'root_dir' ) . '/load.php';

setup_utils();

WP_Mock::bootstrap();

build_sample_plugin();

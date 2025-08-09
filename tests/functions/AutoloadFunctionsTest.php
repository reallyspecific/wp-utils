<?php

namespace ReallySpecific\Utils\Tests\Functions;

use function ReallySpecific\Utils\Tests\config;
use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Assets;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class AutoloadFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_assets_dir() : void
	{
		$this->assertEquals( config( 'sample_plugin_dir' ) . '/dependencies/reallyspecific/wp-utils/assets/', Assets\path() );
	}

	public function test_assets_url() : void
	{
		$this->assertEquals( config( 'sample_plugin_url' ) . '/dependencies/reallyspecific/wp-utils/assets/', Assets\url() );
	}


}
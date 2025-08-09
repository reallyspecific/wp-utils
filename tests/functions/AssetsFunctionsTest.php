<?php

namespace ReallySpecific\Utils\Tests\Functions;

use function ReallySpecific\Utils\Tests\config;

use ReallySpecific\Utils\Tests\Traits\MockFunctions;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Assets;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class AssetsFunctionsTest extends WP_Mock\Tools\TestCase
{
	use MockFunctions;

	public function test_path() : void
	{
		$asset = Assets\path( 'admin-fields.css' );
		$this->assertEquals( config( 'sample_plugin_dir' ) . '/dependencies/reallyspecific/wp-utils/assets/admin-fields.min.css', $asset );

		$asset = Assets\path( 'admin-fields.css', true );
		$this->assertEquals( config( 'sample_plugin_dir' ) . '/dependencies/reallyspecific/wp-utils/assets/admin-fields.css', $asset );
	}

	public function test_url() : void
	{

		$this->mock_functions( [ 'plugins_url' => 'https://example.com' ] );

		$asset = Assets\url( 'admin-fields.js' );
		$this->assertEquals( config( 'sample_plugin_url' ) . '/dependencies/reallyspecific/wp-utils/assets/admin-fields.min.js', $asset );

		$asset = Assets\url( 'admin-fields.js', true );
		$this->assertEquals( config( 'sample_plugin_url' ) . '/dependencies/reallyspecific/wp-utils/assets/admin-fields.js', $asset );
	}
}
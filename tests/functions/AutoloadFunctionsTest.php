<?php

namespace ReallySpecific\Utils\Tests\Functions;

use function ReallySpecific\Utils\Tests\config;
use function ReallySpecific\Utils\assets_dir;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class AutoloadFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_assets_dir() : void
	{
		$this->assertEquals( config( 'root_dir' ) . '/assets', assets_dir() );
	}


}
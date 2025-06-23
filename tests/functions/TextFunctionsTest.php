<?php

namespace ReallySpecific\Utils\Tests\Functions;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Text;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class TextFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_array_to_attr_string() : void
	{
		$this->assertEquals( 'class="test"', Text\array_to_attr_string( [ 'class' => 'test' ] ) );
		$this->assertEquals( 'class="test" data-test="value"', Text\array_to_attr_string( [ 'class' => 'test', 'data-test' => 'value' ] ) );
	}

}
<?php

namespace ReallySpecific\Utils\Tests\Functions;

use function ReallySpecific\Utils\Tests\config;
use ReallySpecific\Utils\Network;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class NetworkFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_get_server_remote_ip() : void
	{
		$ip = Network\get_server_remote_ip();
		$this->assertNotEmpty( $ip );
		$this->assertIsString( $ip );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+\.\d+$/', $ip );
	}

}
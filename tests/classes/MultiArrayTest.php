<?php

namespace ReallySpecific\Utils\Tests\Classes;

use ReallySpecific\Utils\MultiArray;
use ReallySpecific\Utils\Tests\Traits\MockFunctions;

use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class MultiArrayTest extends WP_Mock\Tools\TestCase
{
	use MockFunctions;

	public function test_basic_array_creation() : void
	{

		$basic = new MultiArray( [] );
		$this->assertEquals( [], $basic->to_array() );

		$basic = new MultiArray( (object) [] );
		$this->assertEquals( [], $basic->to_array() );

	}

	public function test_basic_array_with_simple_values() : void
	{

		$this->mock_functions();

		$basic = new MultiArray( [
			'a' => 'b',
			'c' => 'd',
		] );
		$this->assertEquals( [
			'a' => 'b',
			'c' => 'd',
		], $basic->to_array() );

		$basic = new MultiArray( (object) [
			'a' => 'b',
			'c' => 'd',
		] );
		$this->assertEquals( [
			'a' => 'b',
			'c' => 'd',
		], $basic->to_array() );
	}

	public function test_complex_array() : void
	{

		$this->mock_functions();

		$complex = new MultiArray( [
			'a.b'   => 'c',
			'a.d'   => [
				'e'   => 'replace-this',
				'f'   => 'g',
				'h.i' => 'j',
			],
			'a.k.l' => 'm',
			'a.d.e' => 'replaced',
		] );
		$this->assertEquals( [
			'a' => [
				'b' => 'c',
				'd' => [
					'e' => 'replaced',
					'f' => 'g',
					'h' => [
						'i' => 'j',
					],
				],
				'k' => [
					'l' => 'm',
				],
			],
		], $complex->to_array() );

		$complex['n'] = 'o';
		$this->assertEquals( [
			'a' => [
				'b' => 'c',
				'd' => [
					'e' => 'replaced',
					'f' => 'g',
					'h' => [
						'i' => 'j',
					],
				],
				'k' => [
					'l' => 'm',
				],
			],
			'n' => 'o',
		], $complex->to_array() );

		$complex['a.d.e'] = 'replaced-again';
		$this->assertEquals( [
			'a' => [
				'b' => 'c',
				'd' => [
					'e' => 'replaced-again',
					'f' => 'g',
					'h' => [
						'i' => 'j',
					],
				],
				'k' => [
					'l' => 'm',
				],
			],
			'n' => 'o',
		], $complex->to_array() );

		$new_part = $complex['a.d'];
		$this->assertInstanceOf( MultiArray::class, $new_part );
		$this->assertEquals( [
			'e' => 'replaced-again',
			'f' => 'g',
			'h' => [
				'i' => 'j',
			],
		], $new_part->to_array() );
		

		$complex['a.d.e'] = null;
		$this->assertEquals( [
			'f' => 'g',
			'h' => [
				'i' => 'j',
			],
		], $complex['a.d']->to_array() );

		unset( $complex['a.b'] );
		$this->assertEquals( [
			'a' => [
				'd' => [
					'f' => 'g',
					'h' => [
						'i' => 'j',
					],
				],
				'k' => [
					'l' => 'm',
				],
			],
			'n' => 'o',
		], $complex->to_array() );

		unset( $complex['a'] );
		$this->assertEquals( [
			'n' => 'o',
		], $complex->to_array() );
		
	}
}
<?php

namespace ReallySpecific\Utils\Tests\Functions;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Posts;
use WP_Mock;
use Mockery;

/**
 * Tests most function contained in the load.php file
 */
final class PostsFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_get_post_by_slug() : void
	{
		

		WP_Mock::userFunction( 'get_posts', [
			'return' => function( $args ) {
				if ( $args['name'] === 'nonexistant' ) {
					return [];
				}

				$wp_post = Mockery::mock('WP_Post');
				$wp_post->allows('to_array')->andReturn( [
					'post_name' => $args['name'],
				] );

				return [ $wp_post ];
			},
		] );

		$post = Posts\get_post_by_slug( 'test' );
		$this->assertNotEmpty( $post );
		$this->assertIsArray( $post );
		$this->assertEquals( 'test', $post['post_name'] );

		$this->assertNull( Posts\get_post_by_slug( 'nonexistant' ) );
	}

}
<?php

namespace ReallySpecific\Utils\Tests\Functions;

use function ReallySpecific\Utils\Tests\config;
use ReallySpecific\Utils\Filesystem;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class FilesystemFunctionsTest extends WP_Mock\Tools\TestCase
{
	public function test_recursive_mk_rm_dir() : void
	{
		$rm_path   = config( 'cache_dir' ) . '/tests/recursive-mk-rm-test';
		$mk_path = $rm_path . '/recursive/folder/Test';
		if ( file_exists( $rm_path ) ) {
			Filesystem\recursive_rm_dir( $rm_path );
			$this->assertFalse( is_dir( $rm_path ) );
		}

		Filesystem\recursive_mk_dir( $mk_path );
		$this->assertTrue( is_dir( $mk_path ) );

		Filesystem\recursive_rm_dir( $rm_path );
		$this->assertFalse( is_dir( $rm_path ) );
	}

	public function test_join_paths() : void
	{
		// assert two folder relative join
		$this->assertEquals( 'a/b', Filesystem\join_path( 'a', 'b' ) );
		$this->assertEquals( 'a/b', Filesystem\join_path( 'a', '/b' ) );
		$this->assertEquals( 'a/b', Filesystem\join_path( 'a', 'b/' ) );
		$this->assertEquals( 'a/b', Filesystem\join_path( 'a', '/b/' ) );

		// assert three folder relative join
		$this->assertEquals( 'a/b/c', Filesystem\join_path( 'a', 'b', 'c' ) );
		$this->assertEquals( 'a/b/c', Filesystem\join_path( 'a', '/b', 'c' ) );
		$this->assertEquals( 'a/b/c', Filesystem\join_path( 'a', 'b', '/c' ) );
		$this->assertEquals( 'a/b/c', Filesystem\join_path( 'a', '/b', '/c' ) );

		// assert absolute path join
		$this->assertEquals( '/a/b/c', Filesystem\join_path( '/a', 'b', 'c' ) );

		// assert absolute url join
		$this->assertEquals( 'https://example.com/a', Filesystem\join_path( 'https://example.com', 'a' ) );
	}

	public function test_canonical_path() : void
	{
		$this->assertEquals( 'a/b', Filesystem\canonical_path( 'a/b' ) );
		$this->assertEquals( 'a/b', Filesystem\canonical_path( 'a/b/' ) );
		$this->assertEquals( 'a/b/c', Filesystem\canonical_path( 'a/b/c' ) );
		$this->assertEquals( 'a/c', Filesystem\canonical_path( 'a/b/../c' ) );
		$this->assertEquals( 'a/c/e', Filesystem\canonical_path( 'a/b/../c/d/../e' ) );
		$this->assertEquals( 'c', Filesystem\canonical_path( 'a/b/../../c' ) );
	}

	public function test_get_extension_from_mime_type() : void
	{
		WP_Mock::userFunction('wp_cache_get', [
			'args'   => [ 'mime_types', 'rs_wp_util' ],
			'return' => null,
		] );

		WP_Mock::userFunction('wp_cache_set');

		$this->assertEquals( 'pdf', Filesystem\get_extension_from_mime_type( 'application/pdf' ) );
		$this->assertEquals( 'acgi|htm|html|htmls|htx|shtml', Filesystem\get_extension_from_mime_type( 'text/html' ) );
		$this->assertNull( Filesystem\get_extension_from_mime_type( 'fake/mime-type' ) );

	}

	public function test_get_extension_from_mime_type_cache() : void
	{
		WP_Mock::userFunction('wp_cache_set');
		WP_Mock::userFunction( 'wp_cache_get', [
			'args'   => [ 'mime_types', 'rs_wp_util' ],
			'return' => [
				'fake/mime-type' => 'fake',
			],
		] );

		$this->assertEquals( 'fake', Filesystem\get_extension_from_mime_type( 'fake/mime-type' ) );
	}

	public function test_make_zip_from_folder(): void {

		$sample_folder = config( 'sample_plugin_dir' );
		$sample_files  = $this->recursive_file_list( $sample_folder );

		Filesystem\make_zip_from_folder( $sample_folder, config( 'cache_dir' ) . '/tests/test-zip.zip' );
		$this->assertFileExists( config( 'cache_dir' ) . '/tests/test-zip.zip' );

		// check contents of zip
		$zip = new \ZipArchive();
		$zip->open( config( 'cache_dir' ) . '/tests/test-zip.zip' );
		$this->assertEquals( count( $sample_files ), $zip->count() );
		$zip->close();

		unlink( config( 'cache_dir' ) . '/tests/test-zip.zip' );

	}

	private function recursive_file_list( $path ) {
		$file_list = [];
		$dir = opendir( $path );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( $file !== '.' ) && ( $file !== '..' ) ) {
				$full = $path . '/' . $file;
				if ( is_dir( $full ) ) {
					$file_list = array_merge( $file_list, $this->recursive_file_list( $full ) );
				} else {
					$file_list[] = $full;
				}
			}
		}
		closedir( $dir );
		return $file_list;
	}

}
<?php

namespace ReallySpecific\Utils\Tests\Classes;

use function ReallySpecific\Utils\Tests\config;
use ReallySpecific\Utils\Plugin;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class PluginClassTest extends WP_Mock\Tools\TestCase
{
	public function test_constructor_failure() : void
	{

		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action() );

		$this->expectException( \Exception::class );

		new IncompletePlugin();

	}

	public function test_basic_constructor() : void
	{

		define( 'WP_PLUGIN_DIR', config( 'plugins_dir' ) );

		WP_Mock::userFunction( 'plugin_basename', [
			'return' => 'basic-plugin/basic-plugin.php',
		] );
		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action() );

		$plugin = new BasicPlugin();

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertEquals( 'Basic Plugin', $plugin->name );
		$this->assertEquals( WP_PLUGIN_DIR . '/basic-plugin/basic-plugin.php', $plugin->file );
		$this->assertEquals( WP_PLUGIN_DIR . '/basic-plugin', $plugin->path );
		$this->assertEquals( 'basic-plugin', $plugin->slug );

	}

	public function test_complex_construtor() : void {

		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action() );

		$plugin = new ComplexPlugin();

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertEquals( 'Complex Plugin', $plugin->name );
		$this->assertEquals( WP_PLUGIN_DIR . '/complex-plugin/complex-plugin.php', $plugin->file );
		$this->assertEquals( WP_PLUGIN_DIR . '/complex-plugin', $plugin->path );
		$this->assertEquals( 'complex-plugin', $plugin->slug );

		$this->assertEmpty( $plugin->domain );
		$this->assertEmpty( $plugin->update_uri );
		$this->assertEmpty( $plugin->get_wp_data() );

	}

	public function test_init_actions() : void {

		WP_Mock::userFunction( 'trailingslashit', $this->mock_trailingslashit() );
		WP_Mock::userFunction( 'sanitize_title', $this->mock_sanitize_title() );
		WP_Mock::userFunction( 'did_action', $this->mock_did_action( true ) );
		WP_Mock::userFunction( 'get_plugin_data', $this->mock_get_plugin_data( DataLoadedPlugin::mock_plugin_data() ) );

		$plugin = new DataLoadedPlugin();

		$this->assertEquals( 'data-loaded', $plugin->domain );
		$this->assertEquals( 'https://example.com/data-loaded/releases', $plugin->update_uri );
		

	}

	private function mock_trailingslashit() {
		return [
			'return' => function( $path ) {
				return rtrim( $path, '/' ) . '/';
			}
		];
	}

	private function mock_sanitize_title() {
		return [
			'return' => function( $name ) {
				$slug = preg_replace( '/[^a-z0-9]/', '-', strtolower( $name ) );
				while( str_contains( $slug, '--' ) ) {
					$slug = str_replace( '--', '-', $slug );
				}
				return $slug;
			}
		];
	}

	private function mock_did_action( $did_it = false ) {
		return [
			'return' => function() use ( $did_it ){
				return $did_it;
			}
		];
	}

	private function mock_get_plugin_data( $data ) {
		return [
			'return' => function() use ( $data ) {
				return $data;
			}
		];
	}

}

class IncompletePlugin extends Plugin {}

class BasicPlugin extends Plugin {
	function __construct() {
		parent::__construct([
			'name' => 'Basic Plugin',
		]);
	}
}

class ComplexPlugin extends Plugin {
	function __construct() {
		parent::__construct([
			'name' => 'Complex Plugin',
			'file' => config( 'plugins_dir' ) . '/complex-plugin/complex-plugin.php',
		]);
	}

}


class DataLoadedPlugin extends Plugin {
	function __construct() {
		parent::__construct([
			'name' => 'Data Loaded Plugin',
			'file' => config( 'plugins_dir' ) . '/data-loaded-plugin/data-loaded-plugin.php',
		]);
	}

	static function mock_plugin_data() {

		return [
			'Name'            => 'Data Loaded Plugin',
			'PluginURI'       => 'https://example.com/data-loaded',
			'Version'         => '1.0.0',
			'Description'     => 'This is a sample plugin for unit testing',
			'Author'          => 'Example author',
			'AuthorURI'       => 'https://example.com/author',
			'TextDomain'      => 'data-loaded',
			'DomainPath'      => '/languages',
			'TestedWP'        => '6.8',
			'RequiresWP'      => '6.6',
			'RequiresPHP'     => '8.2',
			'UpdateURI'       => 'https://example.com/data-loaded/releases',
			'RequiresPlugins' => '',
		];

	}

}
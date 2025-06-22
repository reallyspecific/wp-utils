<?php

namespace ReallySpecific\Utils\Tests\Classes;

use function ReallySpecific\Utils\Tests\config;

use ReallySpecific\Utils\Tests\Traits\MockFunctions;
use ReallySpecific\Utils\Plugin;
use WP_Mock;

/**
 * Tests most function contained in the load.php file
 */
final class PluginClassTest extends WP_Mock\Tools\TestCase
{
	use MockFunctions;

	public function test_constructor_failure() : void
	{

		$this->mock_functions();

		$this->expectException( \Exception::class );

		new IncompletePlugin();

	}

	public function test_basic_constructor() : void
	{

		define( 'WP_PLUGIN_DIR', config( 'plugins_dir' ) );

		WP_Mock::userFunction( 'plugin_basename', [
			'return' => 'basic-plugin/basic-plugin.php',
		] );
		$this->mock_functions();

		$plugin = new BasicPlugin();

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertEquals( 'Basic Plugin', $plugin->name );
		$this->assertEquals( WP_PLUGIN_DIR . '/basic-plugin/basic-plugin.php', $plugin->file );
		$this->assertEquals( WP_PLUGIN_DIR . '/basic-plugin/', $plugin->path );
		$this->assertEquals( 'basic-plugin', $plugin->slug );

	}

	public function test_complex_construtor() : void {

		$this->mock_functions();

		$plugin = new ComplexPlugin();

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertEquals( 'Complex Plugin', $plugin->name );
		$this->assertEquals( WP_PLUGIN_DIR . '/complex-plugin/complex-plugin.php', $plugin->file );
		$this->assertEquals( WP_PLUGIN_DIR . '/complex-plugin/', $plugin->path );
		$this->assertEquals( 'complex-plugin', $plugin->slug );

		$this->assertEmpty( $plugin->domain );
		$this->assertEmpty( $plugin->update_uri );
		$this->assertEmpty( $plugin->get_wp_data() );

	}

	public function test_init_actions() : void {

		$this->mock_functions( [
			'did_action' => true,
			'plugin_data' => DataLoadedPlugin::mock_plugin_data(),
		] );

		$plugin = new DataLoadedPlugin();

		$this->do_action('init');

		$this->assertEquals( 'data-loaded', $plugin->domain );
		$this->assertEquals( 'https://example.com/data-loaded/releases', $plugin->update_uri );
		

	}

}

class IncompletePlugin extends Plugin {}

class BasicPlugin extends Plugin {
	function __construct() {
		parent::__construct([
			'name' => 'Basic Plugin',
			'file' => config( 'plugins_dir' ) . '/basic-plugin/basic-plugin.php',
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
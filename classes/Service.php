<?php

namespace ReallySpecific\WP_Util;

abstract class Service {

	protected $enabled = true;

	protected $plugin = null;

	/**
	 * Constructor for the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Get the plugin object.
	 *
	 * @return Plugin The plugin object.
	 */
	public function get_plugin() {
		return $this->plugin;
	}

	public function is_enabled() {
		return $this->enabled;
	}

	public function enable() {
		$this->enabled = true;
	}

	public function disable() {
		$this->enabled = false;
	}

	public function __get( $name ) {
		switch ( $name ) {
			case 'plugin':
				return $this->get_plugin();
			case 'enabled':
				return $this->is_enabled();
			default:
				return null;
		}
	}
}

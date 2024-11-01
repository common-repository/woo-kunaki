<?php

/**
 * Main class for Woo Kunaki Lite plugin
 *
 * @since 5.5.1
 */

defined( 'ABSPATH' ) || exit;

class Woo_Kunaki_Light {

	/**
	 * The one and only true Woo_Kunaki_Light instance
	 *
	 * @since 5.5.1
	 * @access private
	 * @var object $instance
	 */
	private static $instance;

	/**
	 * Plugin version
	 *
	 * @since 5.5.1
	 * @var string
	 */
	private $version = '5.5.1';

	/**
	 * Instantiate the main class
	 *
	 * This function instantiates the class, initialize all functions and return the object.
	 *
	 * @since 5.5.1
	 * @return object The one and only true Woo_Kunaki_Light instance.
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ( ! self::$instance instanceof Woo_Kunaki_Light ) ) {

			self::$instance = new Woo_Kunaki_Light;
			self::$instance->setup_constants();
			self::$instance->includes();

		}

		return self::$instance;
	}

	/**
	 * Function for setting up constants
	 *
	 * This function is used to set up constants used throughout the plugin.
	 *
	 * @since 5.5.1
	 */
	public function setup_constants() {

		if ( ! defined( 'WOO_KUNAKI_LIGHT_VERSION' ) ) {
			define( 'WOO_KUNAKI_LIGHT_VERSION', $this->version );
		}

		if ( ! defined( 'WOO_KUNAKI_LIGHT_PLUGIN_PATH' ) ) {
			define( 'WOO_KUNAKI_LIGHT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) . '../' );
		}

		if ( ! defined( 'WOO_KUNAKI_LIGHT_PLUGIN_URL' ) ) {
			define( 'WOO_KUNAKI_LIGHT_PLUGIN_URL', plugin_dir_url( __FILE__ ) . '../' );
		}

	}

	/**
	 * Includes all necessary PHP files
	 *
	 * This function is responsible for including all necessary PHP files.
	 *
	 * @since 5.5.1
	 */
	public function includes() {

		require WOO_KUNAKI_LIGHT_PLUGIN_PATH . 'vendor/autoload.php';
		require WOO_KUNAKI_LIGHT_PLUGIN_PATH . 'libraries/class-woo-kunaki-api.php';
		require WOO_KUNAKI_LIGHT_PLUGIN_PATH . 'libraries/class-woo-kunaki-settings.php';
		require WOO_KUNAKI_LIGHT_PLUGIN_PATH . 'libraries/class-woo-kunaki-options.php';

	}
}
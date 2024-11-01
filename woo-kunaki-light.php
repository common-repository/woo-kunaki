<?php
/**
 * Plugin Name: Integration of Kunaki and WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woo-Kunaki/
 * Description: WooCommerce Extension that lets you sell your CDs and DVDs with WooCommerce, and send those orders directly to your Kunaki account.
 * Version: 5.5.4
 * Author: DMWDS: Daniel Ray
 * Author URI: https://dmwds.com
 * Text Domain: Integration-of-Kunaki-and-WooCommerce
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.1
 * License: GPL2
 * Kunaki Woo Light is free software: you can redistribute it and/or modify it  *under the terms of the GNU General Public License as published by the  *Free Software Foundation, either version 2 of the License, or any later  *version.
 * Kunaki Woo Light is distributed in the hope that it will be useful, but  *WITHOUT ANY WARRANTY; without even the implied warranty of  *MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General  *Public License for more details.
 * You should have received a copy of the GNU General Public License along  *with {Plugin Name}. If not, see {License URI}.
 **/

defined( 'ABSPATH' ) || exit;

/**
 * The main function for returning Woo_Kunaki_Light instance
 *
 * @since 5.5.3
 * @since 5.5.4 change check basic and premium plugins by filename
 *
 * @return object The one and only true Woo_Kunaki_Light instance.
 */
function woo_kunaki_light_runner() {

	$active_plugins = array_map( function ( $val ) {
		$plugin_name = explode( '/', $val );

		return isset( $plugin_name[1] ) ? $plugin_name[1] : $val;
	}, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

	$woo_active     = in_array( 'woocommerce.php', $active_plugins );
	$basic_active   = in_array( 'woo-kunaki-basic.php', $active_plugins );
	$premium_active = in_array( 'woo-kunaki-premium.php', $active_plugins );

	if ( $woo_active && ! $basic_active && ! $premium_active ) {

		if ( ! defined( 'WOO_KUNAKI_LIGHT_PLUGIN_FILE' ) ) {
			define( 'WOO_KUNAKI_LIGHT_PLUGIN_FILE', plugin_basename( __FILE__ ) );
		}

		if ( ! class_exists( 'Woo_Kunaki_Light' ) ) {
			include_once dirname( __FILE__ ) . '/libraries/class-woo-kunaki.php';
		}

		return Woo_Kunaki_Light::instance();
	}

}

add_action( 'init', 'woo_kunaki_light_runner', 10 );
<?php

/**
 * Make settings fields in WooCommerce settings
 *
 * @since 5.5.1
 */

class Woo_Kunaki_Settings {

	/**
	 * Woo_Kunaki_Settings constructor.
	 *
	 * @since 5.5.1
	 */
	public function __construct() {

		add_filter( 'woocommerce_general_settings', array( $this, 'add_kunaki_setting' ) );
		add_filter( 'woocommerce_currency', array( $this, 'add_currency' ) );
		add_filter( 'woocommerce_countries', array( $this, 'rename_countries' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'shipping_fee' ) );

	}

	/**
	 * Add fields in WooCommerce settings
	 *
	 * @since 5.5.1
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	function add_kunaki_setting( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {
			if ( isset( $section['id'] )
			     && 'general_options' == $section['id']
			     && isset( $section['type'] )
			     && 'sectionend' == $section['type'] ) {

				$updated_settings[] = array(
					'name'     => __( 'Kunaki UserId', 'wc_kunaki' ),
					'desc_tip' => __( "publisher's user id -- usually an email address.", 'wc_kunaki' ),
					'id'       => 'kunaki_id',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'desc'     => __( 'Sample ID: test@domain.com', 'wc_kunaki' ),
				);

				$updated_settings[] = array(
					'name'     => __( 'Kunaki Password', 'wc_kunaki' ),
					'desc_tip' => __( "publisher's Password.", 'wc_kunaki' ),
					'id'       => 'kunaki_password',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'desc'     => __( '', 'wc_kunaki' ),
				);

				$updated_settings[] = array(
					'name'     => __( 'Include Product Cost', 'wc_kunaki' ),
					'desc_tip' => __( "Add the quantity cost to total.", 'wc_kunaki' ),
					'id'       => 'kunaki_product_cost',
					'type'     => 'checkbox',
					'css'      => 'min-width:300px;',
					'desc'     => __( '', 'wc_kunaki' ),
				);
			}

			$updated_settings[] = $section;
		}

		return $updated_settings;
	}

	/**
	 * Add currency usd
	 * @since 5.5.1
	 *
	 * @return string
	 */
	public function add_currency() {

		return 'USD';
	}

	/**
	 * Get country code in key
	 *
	 * @since 5.5.1
	 *
	 * @param $countries
	 *
	 * @return mixed
	 */
	public function rename_countries( $countries ) {

		$kunaki_countries = Woo_Kunaki_API::countries_list();
		foreach ( $kunaki_countries as $c => $country ) {
			$countries[ $c ] = $country;
		}

		return $countries;
	}

	/**
	 * Add shipping fee
	 *
	 * @since 5.5.1
	 */
	public function shipping_fee() {

		$price = Woo_Kunaki_API::get_shipping_price();
		if ( $price > 0 ) {
			WC()->cart->add_fee( 'Shipping', $price, $taxable = false, $tax_class = '' );
		}

	}
}

/**
 * Run Woo_Kunaki_Settings class
 *
 * @since 5.5.1
 *
 * @return Woo_Kunaki_Settings
 */
function woo_cunaki_settings_runner() {
	
	return new Woo_Kunaki_Settings();
}

woo_cunaki_settings_runner();
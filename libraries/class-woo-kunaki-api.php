<?php

/**
 * Class for work with Kunaki API
 *
 * @since 5.5.1
 */

defined( 'ABSPATH' ) || exit;

class Woo_Kunaki_API {

	/**
	 * Current mode "TEST" or "LIVE"
	 *
	 * @since 5.5.1
	 *
	 * @var mixed
	 */
	public static $mode = 'live';

	/**
	 * Address to kunaki.com api service
	 *
	 * @since 5.5.1
	 *
	 * @var string
	 */
	private static $url = "http://kunaki.com/HTTPService.ASP";

	/**
	 * Address to kunaki.com api service response type XML
	 *
	 * @since 5.5.1
	 *
	 * @var string
	 */
	private static $url_xml = "http://kunaki.com/XMLService.ASP";

	/**
	 * Make new order on kunaki.com
	 *
	 * @since 5.5.1
	 *
	 * @param $order WC_Order object
	 *
	 * @return mixed
	 */
	public static function make_order( $order ) {

		$headers         = array(
			'Content-type: text/xml;charset="utf-8"',
			'Accept: text/xml',
			'Cache-Control: no-cache',
			'Pragma: no-cache',
			'SOAPAction: "run"',
		);
		$product_request = '';
		$shipping_name   = '';
		$order_data      = new WC_Order( $order );

		if ( $order_data->get_billing_country() ) {
			$country = $order_data->get_billing_country();
		} else {
			$country = $order_data->get_shipping_country();
		}

		if ( $order_data->get_billing_state() ) {
			$state = $order_data->get_billing_state();
		} else {
			$state = $order_data->get_shipping_state();
		}

		if ( $order_data->get_billing_postcode() ) {
			$postcode = $order_data->get_billing_postcode();
		} else {
			$postcode = $order_data->get_shipping_postcode();
		}

		if ( $order_data->get_billing_city() ) {
			$city = $order_data->get_billing_city();
		} else {
			$city = $order_data->get_shipping_city();
		}

		$countries = self::countries_list();
		$items     = $order_data->get_items( 'line_item' );

		foreach ( $items as $item ) {
			$_product = $order->get_product_from_item( $item );

			if ( 'bundle' == $_product->get_type() ) {
				continue;
			}

			$quantity = $item->get_quantity();

			//check if variable or simple product
			if ( $item->get_variation_id() != 0 ) {
				$product_id = $item->get_variation_id();
				$product    = new WC_Product_Variation( $product_id );
			} else {
				$product_id = $item->get_product_id();
				$product    = new WC_Product( $product_id );
			}

			if ( get_post_meta( $product->get_id(), 'kunaki_product', true ) == 'yes' ) {
				$product_request .= "&ProductId=" . urlencode( $product->get_sku() ) . "&Quantity=" . absint( $quantity );
			}
		}

		if ( isset( $countries[ $country ] ) && strlen( $countries[ $country ] ) > 1 ) {
			$request = self::$url;
			$request .= '?RequestType=ShippingOptions';
			$request .= $product_request;
			$request .= "&State_Province=" . urlencode( $state );
			$request .= "&PostalCode=" . urlencode( $postcode );
			$request .= "&Country=" . urlencode( $countries[ $country ] );
			$request .= "&ResponseType=XML";

			$response  = wp_remote_get( $request );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( 200 == $http_code ) {
				$body = wp_remote_retrieve_body( $response );

				$xml = @ simplexml_load_string( $body );
				if ( $xml && 0 == intval( $xml->ErrorCode ) && isset( $xml->Option[0] ) ) {
					$shipping_name = (string) $xml->Option[0]->Description;
				}
			}
		}

		if ( $order_data->get_shipping_first_name() ) {
			$first   = $order_data->get_shipping_first_name();
			$last    = $order_data->get_shipping_last_name();
			$company = $order_data->get_shipping_company();
			$add_1   = $order_data->get_shipping_address_1();
			$add_2   = $order_data->get_shipping_address_2();
		} else {
			$first   = $order_data->get_billing_first_name();
			$last    = $order_data->get_billing_last_name();
			$company = $order_data->get_billing_company();
			$add_1   = $order_data->get_billing_address_1();
			$add_2   = $order_data->get_billing_address_2();
		}

		$xmlRequest = '<Order>';
		$xmlRequest .= '	<UserId>' . get_option( 'kunaki_id' ) . '</UserId>';
		$xmlRequest .= '	<Password>' . get_option( 'kunaki_password' ) . '</Password>';
		$xmlRequest .= '	<Mode>' . self::$mode . '</Mode>';
		$xmlRequest .= '	<Name>' . $first . ' ' . $last . '</Name>';
		$xmlRequest .= '	<Company>' . $company . '</Company>';
		$xmlRequest .= '	<Address1>' . $add_1 . '</Address1>';
		$xmlRequest .= '	<Address2>' . $add_2 . '</Address2>';
		$xmlRequest .= '	<City>' . $city . '</City>';
		$xmlRequest .= '	<State_Province>' . $state . '</State_Province>';
		$xmlRequest .= '	<PostalCode>' . $postcode . '</PostalCode>';
		if ( isset( $countries[ $country ] ) ) {
			$xmlRequest .= '	<Country>' . $countries[ $country ] . '</Country>';
		}
		$xmlRequest .= '	<ShippingDescription>' . $shipping_name . '</ShippingDescription>';

		foreach ( $items as $item ) {
			$_product = $order->get_product_from_item( $item );

			if ( 'bundle' == $_product->get_type() ) {
				continue;
			}

			$quantity = $item->get_quantity();

			//check if variable or simple product
			if ( $item->get_variation_id() != 0 ) {
				$product_id = $item->get_variation_id();
				$product    = new WC_Product_Variation( $product_id );
			} else {
				$product_id = $item->get_product_id();
				$product    = new WC_Product( $product_id );
			}
			$xmlRequest .= '	<Product>';
			$xmlRequest .= '		<ProductId>' . $product->get_sku() . '</ProductId>';
			$xmlRequest .= '		<Quantity>' . $quantity . '</Quantity>';
			$xmlRequest .= '	</Product>';

		}

		$xmlRequest .= '</Order>';

		$args      = array(
			'method'      => 'POST',
			'headers'     => $headers,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'body'        => $xmlRequest,
		);
		$response  = wp_remote_post( self::$url_xml, $args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if ( 200 == $http_code ) {
			$body = wp_remote_retrieve_body( $response );

			$xml_body = preg_replace( '/\s\s+/', '', $body );
			$xml_body = str_replace( '<HTML><BODY>', '', $xml_body );

			$xml = @ simplexml_load_string( $xml_body );
			if ( $xml && 0 == intval( $xml->ErrorCode ) && isset( $xml->OrderId ) ) {
				return intval( $xml->OrderId );
			}
		}

		return 0;
	}

	/**
	 * Get price shipping from kunaki.com
	 *
	 * @since 5.5.1
	 *
	 * @return bool|string
	 */
	public static function get_shipping_price() {

		global $woocommerce;

		$product_request = "";

		if ( 'yes' == get_option( 'woocommerce_enable_shipping_calc' )
		     || 'yes' == get_option( 'woocommerce_shipping_cost_requires_address' ) ) {

			$countries = self::countries_list();

			if ( is_array( WC()->cart->cart_contents ) ) {
				foreach ( WC()->cart->cart_contents as $key => $values ) {
					$product_id = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];
					$product    = wc_get_product( $product_id );

					if ( 'bundle' == $product->get_type() ) {
						continue;
					}

					$is_kunaki_product = 'yes' == get_post_meta( $product->get_id(), 'kunaki_product', true );

					if ( $is_kunaki_product && $product->is_virtual() && ! $product->is_downloadable() ) {
						$product_request .= "&ProductId=" . urlencode( $product->get_sku() )
						                    . "&Quantity=" . absint( $values['quantity'] );
					}
				}
			}

			if ( $woocommerce->customer->get_billing_country() ) {
				$country = $woocommerce->customer->get_billing_country();
			} elseif ( $woocommerce->customer->get_shipping_country() ) {
				$country = $woocommerce->customer->get_shipping_country();
			} else {
				$country = $woocommerce->customer->get_country();
			}

			if ( $woocommerce->customer->get_billing_state() ) {
				$state = $woocommerce->customer->get_billing_state();
			} else {
				$state = $woocommerce->customer->get_shipping_state();
			}

			if ( $woocommerce->customer->get_billing_postcode() ) {
				$postcode = $woocommerce->customer->get_billing_postcode();
			} else {
				$postcode = $woocommerce->customer->get_shipping_postcode();
			}

			if ( empty( $state ) ) {
				$state = 'Alaska';
			}

			if ( empty( $postcode ) ) {
				$postcode = '99501';
			}

			if ( empty( $country ) ) {
				$country = 'United States';
			}

			if ( isset( $countries[ $country ] ) && strlen( $countries[ $country ] ) > 0 ) {
				$request = self::$url;
				$request .= '?RequestType=ShippingOptions';
				$request .= $product_request;
				$request .= '&State_Province=' . urlencode( $state );
				$request .= '&PostalCode=' . urlencode( $postcode );
				$request .= '&Country=' . urlencode( $countries[ $country ] );
				$request .= '&ResponseType=XML';

				$response  = wp_remote_get( $request );
				$http_code = wp_remote_retrieve_response_code( $response );

				if ( 200 == $http_code ) {
					$body = wp_remote_retrieve_body( $response );

					$body = wp_remote_retrieve_body( $response );

					$xml = @ simplexml_load_string( $body );
					if ( $xml && 0 == intval( $xml->ErrorCode ) && isset( $xml->Option[0] ) ) {
						return floatval( $xml->Option[0]->Price );
					}

					return $body;
				}
			}
		}

		return false;
	}

	public static function countries_list() {

		global $kunaki_countries;

		$kunaki_countries = array(
			'AR' => 'Argentina',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'BH' => 'Bahrain',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BR' => 'Brazil',
			'BG' => 'Bulgaria',
			'CA' => 'Canada',
			'CL' => 'Chile',
			'CN' => 'China',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JP' => 'Japan',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MT' => 'Malta',
			'MX' => 'Mexico',
			'ME' => 'Montenegro',
			'NL' => 'Netherlands',
			'NZ' => 'New Zealand',
			'NO' => 'Norway',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'QA' => 'Qatar',
			'RO' => 'Romania',
			'IE' => 'Republic of Ireland',
			'SA' => 'Saudi Arabia',
			'RS' => 'Serbia',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'KR' => 'South Korea',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'TW' => 'Taiwan',
			'TH' => 'Thailand',
			'TR' => 'Turkey',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
		);

		return $kunaki_countries;
	}
}
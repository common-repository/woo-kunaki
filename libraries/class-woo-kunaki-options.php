<?php

/**
 * Make options for Kunaki Plugin
 *
 * @since 5.5.1
 */

defined( 'ABSPATH' ) || exit;

class Woo_Kunaki_Options {

	/**
	 * Woo_Kunaki_Options constructor.
	 *
	 * @since 5.5.1
	 */
	public function __construct() {

		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product' ) );

		add_action( 'manage_product_posts_custom_column', array( $this, 'display_custom_column' ), 99, 2 );

		add_action( 'woocommerce_order_status_processing', array( $this, 'send_order_info' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'send_order_info' ), 10, 1 );

		add_action( 'woocommerce_before_checkout_process', array( $this, 'checkout_shipping' ) );

		//Custom order status
		add_action( 'woocommerce_order_actions', array( $this, 'order_action' ) );
		add_action( 'woocommerce_order_action_wc_custom_order_action', array( $this, 'order_meta_box_action' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_filter( 'product_type_options', array( $this, 'add_options' ), 10, 1 );

		add_filter( 'plugin_action_links_' . WOO_KUNAKI_LIGHT_PLUGIN_FILE, array( $this, 'add_upgrade_link' ), 10, 1 );

		add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
	}

	/**
	 * Adding save for Kunaki product
	 *
	 * @since 5.5.1
	 *
	 * @param $post_id
	 */
	public function save_product( $post_id ) {

		$kunaki_product = isset( $_POST['kunaki_product'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, 'kunaki_product', $kunaki_product );

		if ( 'yes' == $kunaki_product ) {
			global $wpdb;
			$wpdb->query( "update " . $wpdb->postmeta . " set meta_value='yes' where post_id='" . intval( $post_id ) . "' and meta_key='_virtual'" );
		}

	}

	/**
	 * Adding options for check product as kunaki product
	 *
	 * @since 5.5.1
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_options( $options ) {

		global $product;

		$product = get_post( get_the_ID() );
		if ( $product->post_type == 'product' ) {
			$kunaki = get_post_meta( get_the_ID(), 'kunaki_product', true );

			if ( ! empty( $kunaki ) ) {
				$options['kunaki'] = array(
					'id'            => 'kunaki_product',
					'wrapper_class' => 'show_if_simple',
					'label'         => __( 'Kunaki Product', 'woocommerce' ),
					'description'   => __( 'Please check if this is a kunaki based product', 'woocommerce' ),
					'default'       => $kunaki
				);
			} else {
				$options['kunaki'] = array(
					'id'            => 'kunaki_product',
					'wrapper_class' => 'show_if_simple',
					'label'         => __( 'Kunaki Product', 'woocommerce' ),
					'description'   => __( 'Please check if this is a kunaki based product', 'woocommerce' ),
					'default'       => 'no'
				);
			}
		}

		return $options;
	}

	/**
	 * Custom column name for kunaki product
	 *
	 * @since 5.5.1
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function display_custom_column( $column, $post_id ) {

		switch ( $column ) {
			case 'name' :
				?>
                <div class="hidden custom_field_demo_inline"
                     id="custom_field_demo_inline_<?php echo esc_html( $post_id ); ?>">
                    <div id="kunaki_product"><?php echo get_post_meta( $post_id, 'kunaki_product', true ); ?></div>
                </div>
				<?php
				break;

			default :
				break;
		}

	}

	/**
	 * Load scripts
	 *
	 * @since  5.5.1
	 */
	public function add_scripts() {

		wp_enqueue_script(
			'woo-kunaki-light-scripts',
			WOO_KUNAKI_LIGHT_PLUGIN_URL . 'assets/scripts.js',
			array( 'jquery' ), WOO_KUNAKI_LIGHT_VERSION,
			true
		);

		wp_enqueue_style(
			'woo-kunaki-light-styles',
			WOO_KUNAKI_LIGHT_PLUGIN_URL . 'assets/styles.css',
			array(),
			WOO_KUNAKI_LIGHT_VERSION
		);

	}

	/**
	 * Send order info to kunaki.com
	 *
	 * @since 5.5.1
	 *
	 * @param $order_id
	 */
	public function send_order_info( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( 1 > get_post_meta( $order_id, 'kunaki_order_id', true ) ) {
			$kunaki_order_id = Woo_Kunaki_API::make_order( $order );

			if ( 0 < $kunaki_order_id ) {
				update_post_meta( $order_id, 'kunaki_order_id', $kunaki_order_id );
				unset( $_COOKIE['k_shipping'] );
				$message = __( 'Order sent to Kunaki.com placed', 'woocommerce' );
				$order->add_order_note( $message, 0 );
			}
		}

	}

	/**
	 * Check shipping to selected country
	 *
	 * @since 5.5.1
	 */
	public function checkout_shipping() {

		global $woocommerce;

		$countries     = Woo_Kunaki_API::countries_list();
		$product_names = '';

		if ( is_array( WC()->cart->cart_contents ) ) {
			$k = 1;
			foreach ( WC()->cart->cart_contents as $key => $values ) {
				$_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
				if ( get_post_meta( $_product->get_id(), 'kunaki_product', true ) == 'yes' ) {
					$product_names .= "<div>" . $k . ": " . get_the_title( $_product->get_id() ) . "</div>";
					$count         = 1;
					$k ++;
				}
			}
		}

		if ( strlen( $countries[ $woocommerce->customer->get_shipping_country() ] ) < 1 && $count == 1 ) {
			$error = "<hr>We're sorry, but we cannot ship the following items, to your selected country." . $product_names;
			$error .= "<a href='" . get_bloginfo( 'url' ) . "/cart'>Click here</a> to remove this item from your cart and 
you will be able to check out normally, with the rest of the items in your cart.<hr>";
			$woocommerce->add_error( $error );
		}

	}

	/**
	 * Add a custom action to order actions select box on edit order page
	 * Only added for paid orders that haven't fired this action yet
	 *
	 * @since 5.5.1
	 *
	 * @param array $actions order actions array to display
	 *
	 * @return array - updated actions
	 */
	public function order_action( $actions ) {

		global $theorder;

		if ( ! $theorder->is_paid() || get_post_meta( $theorder->get_id(), 'kunaki_order_id', true ) ) {
			return $actions;
		}

		$actions['wc_custom_order_action'] = __( 'Send order to kunaki', 'kunaki' );

		return $actions;
	}

	/**
	 * Add an order note when custom action is clicked
	 * Add a flag on the order to show it's been run
	 *
	 * @since 5.5.1
	 *
	 * @param \WC_Order $order
	 */
	function order_meta_box_action( $order ) {

		kunaki_post( $order->get_id() );

	}

	/**
	 * Add link for upgrade plugin
	 *
	 * @since 5.5.1
	 *
	 * @param $links
	 * @param $plugin_file
	 *
	 * @return array
	 */
	public function add_upgrade_link( $links ) {

		$links[] = '<span><a href="https://dmwds.com/shop/kunaki-woo/" class="woo-kunaki-upgrade-link" target="_blank" >'
		           . __( 'Upgrade to Basic', 'kunaki' ) . '</a></span>';

		return $links;
	}

	/**
	 * Display notice about upgrade plugin
	 *
	 * @since 5.5.1
	 */
	public function upgrade_notice() {

		?>
        <div class="error notice">
            <p>
				<?php _e( 'Upgrade to Basic or Premium and get great benefits like  Variation, multiple Sku, and Kunaki shipping rates! Buy Today : ', 'kunaki' ); ?>
                <a href="https://dmwds.com/shop/kunaki-woo/" target="_blank"
                   class="kunaki-upgrade-btn"><?php _e( 'Upgrade Now', 'kunaki' ); ?></a>
            </p>
        </div>
		<?php

	}

}

/**
 * Run Woo_Kunaki_Options class
 *
 * @since 5.5.1
 *
 * @return Woo_Kunaki_Options
 */
function woo_kunaki_options_runner() {

	return new Woo_Kunaki_Options();
}

woo_kunaki_options_runner();
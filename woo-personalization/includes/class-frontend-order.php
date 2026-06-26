<?php
/**
 * Frontend order views: thank you page and My Account view order.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Frontend_Order
 */
class WCP_Frontend_Order {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'render_order_item_personalization' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue styles on order confirmation and view-order pages.
	 */
	public static function enqueue_assets() {
		if ( ! is_order_received_page() && ! is_wc_endpoint_url( 'view-order' ) ) {
			return;
		}

		wp_enqueue_style(
			'wcp-order-personalization',
			WCP_PLUGIN_URL . 'assets/css/order-personalization.css',
			array(),
			WCP_VERSION
		);
	}

	/**
	 * Show mockup preview for personalized line items.
	 *
	 * @param int                   $item_id    Item ID.
	 * @param WC_Order_Item_Product $item       Order item.
	 * @param WC_Order              $order      Order object.
	 * @param bool                  $plain_text Plain text email context.
	 */
	public static function render_order_item_personalization( $item_id, $item, $order, $plain_text ) {
		unset( $item_id, $order );

		if ( $plain_text || is_admin() || WCP_Plugin::is_rendering_order_email() ) {
			return;
		}

		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		if ( 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
			return;
		}

		$mockup_url = WCP_Cart_Order::get_order_mockup_url( $item );

		echo '<div class="wcp-order-personalization wcp-order-personalization--frontend">';
		echo '<p class="wcp-order-personalization-label"><strong>' . esc_html__( 'Your custom design', 'woo-personalization' ) . '</strong></p>';

		if ( $mockup_url ) {
			echo '<p class="wcp-order-mockup">';
			echo '<a href="' . esc_url( $mockup_url ) . '" target="_blank" rel="noopener noreferrer">';
			echo '<img src="' . esc_url( $mockup_url ) . '" alt="' . esc_attr__( 'Your mockup preview', 'woo-personalization' ) . '" class="wcp-order-mockup-thumb" loading="lazy" />';
			echo '</a></p>';
		} else {
			echo '<p class="wcp-order-personalization-unavailable">' . esc_html__( 'Mockup preview is being prepared for this item.', 'woo-personalization' ) . '</p>';
		}

		echo '</div>';
	}
}

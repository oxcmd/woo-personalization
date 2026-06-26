<?php
/**
 * Order email personalization output.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Order_Email
 */
class WCP_Order_Email {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'render_item_mockup' ), 12, 4 );
	}

	/**
	 * Append mockup preview to personalized items in HTML order emails.
	 *
	 * @param int                   $item_id    Item ID.
	 * @param WC_Order_Item_Product $item       Order item.
	 * @param WC_Order              $order      Order object.
	 * @param bool                  $plain_text Plain text email context.
	 */
	public static function render_item_mockup( $item_id, $item, $order, $plain_text ) {
		unset( $item_id, $order );

		if ( ! WCP_Plugin::is_rendering_order_email() ) {
			return;
		}

		if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Custom design: personalized mockup attached to this item.', 'woo-personalization' ) . "\n";
			return;
		}

		$mockup_url = WCP_Cart_Order::get_order_mockup_url( $item );
		if ( ! $mockup_url ) {
			echo '<p style="margin:8px 0 0;font-size:13px;color:#646970;">' . esc_html__( 'Your custom design mockup is being prepared.', 'woo-personalization' ) . '</p>';
			return;
		}

		printf(
			'<div style="margin-top:10px;"><p style="margin:0 0 6px;font-size:13px;font-weight:600;">%1$s</p><a href="%2$s" style="display:inline-block;"><img src="%2$s" alt="%1$s" width="120" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;display:block;" /></a></div>',
			esc_html__( 'Your custom design', 'woo-personalization' ),
			esc_url( $mockup_url )
		);
	}
}

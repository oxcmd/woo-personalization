<?php
/**
 * Production notes for personalized orders.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Order_Notes
 */
class WCP_Order_Notes {

	const NOTE_FLAG = '_wcp_production_note_added';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'add_production_note' ), 25, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'add_production_note_from_store_api' ), 25, 1 );
	}

	/**
	 * Add note after classic checkout.
	 *
	 * @param int           $order_id    Order ID.
	 * @param array         $posted_data Posted data.
	 * @param WC_Order|null $order       Order object.
	 */
	public static function add_production_note( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		self::maybe_add_note( $order );
	}

	/**
	 * Add note after block checkout.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function add_production_note_from_store_api( $order ) {
		self::maybe_add_note( $order );
	}

	/**
	 * Create a private admin note when the order includes personalized items.
	 *
	 * @param WC_Order|null $order Order object.
	 */
	private static function maybe_add_note( $order ) {
		if ( ! $order instanceof WC_Order || ! WCP_Cart_Order::order_has_personalization( $order ) ) {
			return;
		}

		if ( 'yes' === $order->get_meta( self::NOTE_FLAG ) ) {
			return;
		}

		$count = 0;
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product && 'yes' === $item->get_meta( '_wcp_personalized' ) ) {
				++$count;
			}
		}

		if ( $count < 1 ) {
			return;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %d: number of personalized line items */
				_n(
					'Production: %d personalized item — design files are ready in the order admin (mockup + original upload).',
					'Production: %d personalized items — design files are ready in the order admin (mockups + original uploads).',
					$count,
					'woo-personalization'
				),
				$count
			),
			false,
			true
		);

		$order->update_meta_data( self::NOTE_FLAG, 'yes' );
		$order->save();
	}
}

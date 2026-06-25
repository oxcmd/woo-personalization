<?php
/**
 * WooCommerce REST API personalization fields.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Rest_Api
 */
class WCP_Rest_Api {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'add_order_personalization_summary' ), 20, 3 );
		add_filter( 'woocommerce_rest_prepare_order_item_object', array( __CLASS__, 'add_line_item_personalization' ), 20, 3 );
	}

	/**
	 * Add order-level personalization summary to REST responses.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WC_Order         $order    Order object.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response
	 */
	public static function add_order_personalization_summary( $response, $order, $request ) {
		unset( $request );

		if ( ! $order instanceof WC_Order ) {
			return $response;
		}

		$data = $response->get_data();
		$data['wcp_personalization'] = array(
			'has_personalized_items' => WCP_Cart_Order::order_has_personalization( $order ),
			'personalized_item_count'  => self::count_personalized_items( $order ),
		);

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Add personalization payload to order line items in REST responses.
	 *
	 * @param WP_REST_Response        $response Response object.
	 * @param WC_Order_Item_Product $item     Order item.
	 * @param WP_REST_Request       $request  Request object.
	 * @return WP_REST_Response
	 */
	public static function add_line_item_personalization( $response, $item, $request ) {
		unset( $request );

		if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
			return $response;
		}

		$mockup_url = WCP_Cart_Order::get_order_mockup_url( $item );
		$data       = $response->get_data();

		$data['wcp_personalization'] = array(
			'personalized'  => true,
			'template_id'   => (int) $item->get_meta( '_wcp_template_id' ),
			'mockup_url'    => $mockup_url ? $mockup_url : null,
			'has_original'  => (bool) WCP_Cart_Order::get_order_original_path( $item ),
			'has_mockup'    => (bool) WCP_Cart_Order::get_order_mockup_path( $item ),
		);

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Count personalized line items on an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return int
	 */
	private static function count_personalized_items( $order ) {
		$count = 0;

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product && 'yes' === $item->get_meta( '_wcp_personalized' ) ) {
				++$count;
			}
		}

		return $count;
	}
}

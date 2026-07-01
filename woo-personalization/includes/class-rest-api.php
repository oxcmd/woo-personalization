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
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'add_order_personalization' ), 20, 3 );
	}

	/**
	 * Add personalization summary and line item fields to REST responses.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @param WC_Order         $order    Order object.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response
	 */
	public static function add_order_personalization( $response, $order, $request ) {
		unset( $request );

		if ( ! $order instanceof WC_Order ) {
			return $response;
		}

		$data = $response->get_data();
		$data['wcp_personalization'] = array(
			'has_personalized_items'  => WCP_Cart_Order::order_has_personalization( $order ),
			'personalized_item_count' => self::count_personalized_items( $order ),
		);

		if ( ! empty( $data['line_items'] ) && is_array( $data['line_items'] ) ) {
			foreach ( $data['line_items'] as $index => $line_item ) {
				$item_id = isset( $line_item['id'] ) ? absint( $line_item['id'] ) : 0;
				if ( ! $item_id ) {
					continue;
				}

				$item = $order->get_item( $item_id );
				if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
					continue;
				}

				$mockup_url = WCP_Cart_Order::get_order_mockup_url( $item );
				$data['line_items'][ $index ]['wcp_personalization'] = array(
					'personalized'     => true,
					'template_id'      => (int) $item->get_meta( '_wcp_template_id' ),
					'mockup_url'       => $mockup_url ? $mockup_url : null,
					'has_original'     => (bool) WCP_Cart_Order::get_order_original_path( $item ),
					'has_mockup'       => (bool) WCP_Cart_Order::get_order_mockup_path( $item ),
					'design_transform' => self::get_item_design_transform( $item ),
				);
			}
		}

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

	/**
	 * Decode stored design transform from an order item.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return array{scale: float, offset_x: float, offset_y: float}|null
	 */
	private static function get_item_design_transform( $item ) {
		$raw = (string) $item->get_meta( '_wcp_design_transform' );
		if ( ! $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return WCP_Plugin::sanitize_design_transform( $decoded );
	}
}

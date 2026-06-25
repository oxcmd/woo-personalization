<?php
/**
 * Admin orders list filter for personalized orders.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Admin_Orders_Filter
 */
class WCP_Admin_Orders_Filter {

	const ORDER_META_FLAG = '_wcp_has_personalization';
	const FILTER_KEY      = 'wcp_personalized';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( __CLASS__, 'render_filter_hpos' ), 20 );
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( __CLASS__, 'filter_query_hpos' ), 20 );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_filter_legacy' ), 20, 2 );
		add_filter( 'request', array( __CLASS__, 'filter_query_legacy' ), 20 );
	}

	/**
	 * Render filter dropdown on HPOS orders screen.
	 *
	 * @param string $order_type Order type.
	 */
	public static function render_filter_hpos( $order_type ) {
		if ( 'shop_order' !== $order_type ) {
			return;
		}

		self::render_dropdown();
	}

	/**
	 * Render filter dropdown on legacy orders screen.
	 *
	 * @param string $post_type Post type.
	 * @param string $which     Top or bottom of table.
	 */
	public static function render_filter_legacy( $post_type, $which ) {
		if ( 'shop_order' !== $post_type || 'top' !== $which ) {
			return;
		}

		self::render_dropdown();
	}

	/**
	 * Output shared filter control.
	 */
	private static function render_dropdown() {
		$current = isset( $_GET[ self::FILTER_KEY ] ) ? sanitize_key( wp_unslash( $_GET[ self::FILTER_KEY ] ) ) : '';

		echo '<select name="' . esc_attr( self::FILTER_KEY ) . '" id="' . esc_attr( self::FILTER_KEY ) . '">';
		echo '<option value="">' . esc_html__( 'All designs', 'woo-personalization' ) . '</option>';
		echo '<option value="yes"' . selected( $current, 'yes', false ) . '>' . esc_html__( 'Personalized orders only', 'woo-personalization' ) . '</option>';
		echo '</select>';
	}

	/**
	 * Apply filter to HPOS order queries.
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @return array<string, mixed>
	 */
	public static function filter_query_hpos( $query_args ) {
		if ( empty( $_GET[ self::FILTER_KEY ] ) || 'yes' !== sanitize_key( wp_unslash( $_GET[ self::FILTER_KEY ] ) ) ) {
			return $query_args;
		}

		$query_args['meta_query']   = $query_args['meta_query'] ?? array();
		$query_args['meta_query'][] = array(
			'key'   => self::ORDER_META_FLAG,
			'value' => 'yes',
		);

		return $query_args;
	}

	/**
	 * Apply filter to legacy post-based order queries.
	 *
	 * @param array<string, mixed> $query_vars Query vars.
	 * @return array<string, mixed>
	 */
	public static function filter_query_legacy( $query_vars ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $query_vars['post_type'] ) || 'shop_order' !== $query_vars['post_type'] ) {
			return $query_vars;
		}

		if ( empty( $_GET[ self::FILTER_KEY ] ) || 'yes' !== sanitize_key( wp_unslash( $_GET[ self::FILTER_KEY ] ) ) ) {
			return $query_vars;
		}

		$query_vars['meta_key']   = self::ORDER_META_FLAG;
		$query_vars['meta_value'] = 'yes';

		return $query_vars;
	}

	/**
	 * Persist order-level personalization flag.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function flag_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( WCP_Cart_Order::order_has_personalization( $order ) ) {
			$order->update_meta_data( self::ORDER_META_FLAG, 'yes' );
		} else {
			$order->delete_meta_data( self::ORDER_META_FLAG );
		}
	}
}

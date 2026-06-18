<?php
/**
 * Admin orders list personalization column.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Admin_Orders_List
 */
class WCP_Admin_Orders_List {

	const COLUMN_KEY = 'wcp_personalization';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		if ( self::is_hpos_enabled() ) {
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_column' ), 20 );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_column_hpos' ), 20, 2 );
			return;
		}

		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_column_legacy' ), 20, 2 );
	}

	/**
	 * Enqueue admin styles on the orders list screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$allowed_screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );
		if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
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
	 * Whether WooCommerce HPOS is enabled.
	 *
	 * @return bool
	 */
	private static function is_hpos_enabled() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		return false;
	}

	/**
	 * Insert personalization column after order status.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function add_column( $columns ) {
		$reordered = array();

		foreach ( $columns as $key => $label ) {
			$reordered[ $key ] = $label;

			if ( 'order_status' === $key ) {
				$reordered[ self::COLUMN_KEY ] = esc_html__( 'Design', 'woo-personalization' );
			}
		}

		if ( ! isset( $reordered[ self::COLUMN_KEY ] ) ) {
			$reordered[ self::COLUMN_KEY ] = esc_html__( 'Design', 'woo-personalization' );
		}

		return $reordered;
	}

	/**
	 * Render column content for HPOS orders table.
	 *
	 * @param string    $column Column key.
	 * @param WC_Order  $order  Order object.
	 */
	public static function render_column_hpos( $column, $order ) {
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}

		self::render_column_content( $order );
	}

	/**
	 * Render column content for legacy post-based orders table.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Order post ID.
	 */
	public static function render_column_legacy( $column, $post_id ) {
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			echo '&mdash;';
			return;
		}

		self::render_column_content( $order );
	}

	/**
	 * Output thumbnail or badge for personalized orders.
	 *
	 * @param WC_Order $order Order object.
	 */
	private static function render_column_content( $order ) {
		if ( ! $order instanceof WC_Order ) {
			echo '&mdash;';
			return;
		}

		if ( ! WCP_Cart_Order::order_has_personalization( $order ) ) {
			echo '&mdash;';
			return;
		}

		$mockup_url = WCP_Cart_Order::get_first_order_mockup_url( $order );
		$label      = esc_attr__( 'Personalized order', 'woo-personalization' );

		if ( $mockup_url ) {
			printf(
				'<span class="wcp-orders-list-personalization" title="%1$s"><img src="%2$s" alt="%1$s" class="wcp-orders-list-thumb" loading="lazy" /></span>',
				$label,
				esc_url( $mockup_url )
			);
			return;
		}

		printf(
			'<span class="wcp-orders-list-personalization wcp-orders-list-personalization--badge" title="%1$s"><span class="dashicons dashicons-art" aria-hidden="true"></span><span class="screen-reader-text">%1$s</span></span>',
			$label
		);
	}
}

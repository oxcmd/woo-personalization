<?php
/**
 * Customer My Account orders list personalization column.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Frontend_Orders_List
 */
class WCP_Frontend_Orders_List {

	const COLUMN_KEY = 'wcp_design';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_account_orders_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'woocommerce_account_orders_column_' . self::COLUMN_KEY, array( __CLASS__, 'render_column' ), 10, 1 );
		add_filter( 'woocommerce_my_account_my_orders_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'woocommerce_my_account_my_orders_column_' . self::COLUMN_KEY, array( __CLASS__, 'render_column' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue styles on the My Account orders page.
	 */
	public static function enqueue_assets() {
		if ( ! is_account_page() || ! is_wc_endpoint_url( 'orders' ) ) {
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
	 * Insert design column before the actions column.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function add_column( $columns ) {
		if ( isset( $columns[ self::COLUMN_KEY ] ) ) {
			return $columns;
		}

		$reordered = array();

		foreach ( $columns as $key => $label ) {
			if ( 'order-actions' === $key ) {
				$reordered[ self::COLUMN_KEY ] = esc_html__( 'Design', 'woo-personalization' );
			}

			$reordered[ $key ] = $label;
		}

		if ( ! isset( $reordered[ self::COLUMN_KEY ] ) ) {
			$reordered[ self::COLUMN_KEY ] = esc_html__( 'Design', 'woo-personalization' );
		}

		return $reordered;
	}

	/**
	 * Render design thumbnail for a customer order row.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function render_column( $order ) {
		if ( ! $order instanceof WC_Order ) {
			echo '&mdash;';
			return;
		}

		if ( ! WCP_Cart_Order::order_has_personalization( $order ) ) {
			echo '&mdash;';
			return;
		}

		$mockup_url = WCP_Cart_Order::get_first_order_mockup_url( $order );
		$label      = esc_attr__( 'Your custom design', 'woo-personalization' );

		if ( $mockup_url ) {
			printf(
				'<span class="wcp-my-account-design" title="%1$s"><img src="%2$s" alt="%1$s" class="wcp-my-account-design-thumb" loading="lazy" width="48" height="48" /></span>',
				$label,
				esc_url( $mockup_url )
			);
			return;
		}

		echo '<span class="wcp-my-account-design wcp-my-account-design--badge" title="' . esc_attr__( 'Personalized order', 'woo-personalization' ) . '">&#9733;</span>';
	}
}

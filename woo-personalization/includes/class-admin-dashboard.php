<?php
/**
 * WooCommerce admin dashboard widget for recent personalized orders.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Admin_Dashboard
 */
class WCP_Admin_Dashboard {

	const WIDGET_ID = 'wcp_recent_personalized_orders';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register dashboard widget for shop managers.
	 */
	public static function register_widget() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			self::WIDGET_ID,
			__( 'Recent personalized orders', 'woo-personalization' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	/**
	 * Enqueue widget styles on dashboard.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix ) {
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
	 * Render widget content.
	 */
	public static function render_widget() {
		$orders = self::get_recent_personalized_orders( 5 );

		if ( empty( $orders ) ) {
			echo '<p>' . esc_html__( 'No personalized orders yet.', 'woo-personalization' ) . '</p>';
			return;
		}

		echo '<ul class="wcp-dashboard-orders">';

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$mockup_url = WCP_Cart_Order::get_first_order_mockup_url( $order );
			$edit_url   = $order->get_edit_order_url();
			$label      = sprintf(
				/* translators: %s: order number */
				__( 'Order #%s', 'woo-personalization' ),
				$order->get_order_number()
			);

			echo '<li class="wcp-dashboard-order">';
			echo '<a class="wcp-dashboard-order-link" href="' . esc_url( $edit_url ) . '">';

			if ( $mockup_url ) {
				echo '<img src="' . esc_url( $mockup_url ) . '" alt="" class="wcp-dashboard-order-thumb" loading="lazy" width="48" height="48" />';
			} else {
				echo '<span class="wcp-dashboard-order-thumb wcp-dashboard-order-thumb--placeholder" aria-hidden="true">&#9733;</span>';
			}

			echo '<span class="wcp-dashboard-order-meta">';
			echo '<strong>' . esc_html( $label ) . '</strong>';
			echo '<span>' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . ' &middot; ' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span>';
			echo '</span>';
			echo '</a></li>';
		}

		echo '</ul>';

		$orders_url = admin_url( 'admin.php?page=wc-orders' );
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) || ! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$orders_url = admin_url( 'edit.php?post_type=shop_order' );
		}

		echo '<p class="wcp-dashboard-footer"><a href="' . esc_url( $orders_url ) . '">' . esc_html__( 'View all orders', 'woo-personalization' ) . '</a></p>';
	}

	/**
	 * Fetch recent orders that contain personalized line items.
	 *
	 * @param int $limit Max orders to return.
	 * @return WC_Order[]
	 */
	private static function get_recent_personalized_orders( $limit = 5 ) {
		$limit  = max( 1, absint( $limit ) );
		$orders = wc_get_orders(
			array(
				'limit'   => 50,
				'orderby' => 'date',
				'order'   => 'DESC',
				'return'  => 'objects',
			)
		);

		$personalized = array();
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order || ! WCP_Cart_Order::order_has_personalization( $order ) ) {
				continue;
			}

			$personalized[] = $order;
			if ( count( $personalized ) >= $limit ) {
				break;
			}
		}

		return $personalized;
	}
}

<?php
/**
 * Admin order display and secure file downloads.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Admin_Order
 */
class WCP_Admin_Order {

	const DOWNLOAD_ACTION = 'wcp_download_file';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_after_order_itemmeta', array( __CLASS__, 'render_order_item_meta' ), 10, 3 );
		add_action( 'wp_ajax_' . self::DOWNLOAD_ACTION, array( __CLASS__, 'handle_download' ) );
	}

	/**
	 * Render personalization details on order line item.
	 *
	 * @param int                   $item_id Item ID.
	 * @param WC_Order_Item_Product $item    Order item.
	 * @param WC_Product|null       $product Product object.
	 */
	public static function render_order_item_meta( $item_id, $item, $product ) {
		unset( $product );

		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		if ( 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
			return;
		}

		$mockup_url = WCP_Cart_Order::get_order_mockup_url( $item );
		$order_id   = $item->get_order_id();
		$original   = self::get_download_url( $order_id, $item_id, 'original' );
		$mockup_dl  = self::get_download_url( $order_id, $item_id, 'mockup' );

		echo '<div class="wcp-order-personalization">';
		echo '<p><strong>' . esc_html__( 'Personalized design', 'woo-personalization' ) . '</strong></p>';

		if ( $mockup_url ) {
			echo '<p class="wcp-order-mockup">';
			echo '<a href="' . esc_url( $mockup_url ) . '" target="_blank" rel="noopener noreferrer">';
			echo '<img src="' . esc_url( $mockup_url ) . '" alt="' . esc_attr__( 'Mockup preview', 'woo-personalization' ) . '" class="wcp-order-mockup-thumb" />';
			echo '</a></p>';
		} else {
			echo '<p class="wcp-admin-warning">' . esc_html__( 'Mockup preview file is unavailable for this order item.', 'woo-personalization' ) . '</p>';
		}

		if ( $original ) {
			echo '<p><a class="button" href="' . esc_url( $original ) . '">' . esc_html__( 'Download original upload', 'woo-personalization' ) . '</a></p>';
		}

		if ( $mockup_dl ) {
			echo '<p><a class="button" href="' . esc_url( $mockup_dl ) . '">' . esc_html__( 'Download mockup preview', 'woo-personalization' ) . '</a></p>';
		}

		echo '</div>';
	}

	/**
	 * Build secure download URL.
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $item_id  Item ID.
	 * @param string $type     original|mockup.
	 * @return string|false
	 */
	public static function get_download_url( $order_id, $item_id, $type = 'original' ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$item = WC_Order_Factory::get_order_item( $item_id );
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return false;
		}

		$path = 'mockup' === $type
			? WCP_Cart_Order::get_order_mockup_path( $item )
			: WCP_Cart_Order::get_order_original_path( $item );

		if ( empty( $path ) || ! file_exists( $path ) ) {
			return false;
		}

		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => self::DOWNLOAD_ACTION,
					'order_id' => absint( $order_id ),
					'item_id'  => absint( $item_id ),
					'type'     => sanitize_key( $type ),
				),
				admin_url( 'admin-ajax.php' )
			),
			'wcp_download_' . absint( $order_id ) . '_' . absint( $item_id ),
			'nonce'
		);
	}

	/**
	 * Handle secure file download.
	 */
	public static function handle_download() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$item_id  = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
		$type     = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'original';

		if ( ! $order_id || ! $item_id ) {
			wp_die( esc_html__( 'Invalid download request.', 'woo-personalization' ), 400 );
		}

		check_ajax_referer( 'wcp_download_' . $order_id . '_' . $item_id, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'woo-personalization' ), 403 );
		}

		$item = WC_Order_Factory::get_order_item( $item_id );
		if ( ! $item instanceof WC_Order_Item_Product || (int) $item->get_order_id() !== $order_id ) {
			wp_die( esc_html__( 'Order item not found.', 'woo-personalization' ), 404 );
		}

		$path = 'mockup' === $type
			? WCP_Cart_Order::get_order_mockup_path( $item )
			: WCP_Cart_Order::get_order_original_path( $item );

		if ( empty( $path ) || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'woo-personalization' ), 404 );
		}

		$filename = basename( $path );
		$mime     = wp_check_filetype( $filename )['type'] ?: 'application/octet-stream';

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}

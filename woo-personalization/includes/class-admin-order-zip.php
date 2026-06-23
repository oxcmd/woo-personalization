<?php
/**
 * Admin order ZIP download for all personalization files.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Admin_Order_Zip
 */
class WCP_Admin_Order_Zip {

	const ZIP_ACTION = 'wcp_download_order_zip';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ), 30 );
		add_action( 'wp_ajax_' . self::ZIP_ACTION, array( __CLASS__, 'handle_download' ) );
	}

	/**
	 * Register order meta box on HPOS and legacy screens.
	 */
	public static function add_meta_box() {
		if ( ! class_exists( 'WC_Order' ) ) {
			return;
		}

		$screen = self::get_order_screen_id();
		if ( ! $screen ) {
			return;
		}

		add_meta_box(
			'wcp_order_zip',
			__( 'Personalization files', 'woo-personalization' ),
			array( __CLASS__, 'render_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Resolve admin order screen ID.
	 *
	 * @return string|false
	 */
	private static function get_order_screen_id() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
				? wc_get_page_screen_id( 'shop-order' )
				: 'shop_order';
		}

		return 'shop_order';
	}

	/**
	 * Render ZIP download button.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public static function render_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order );
		if ( ! $order instanceof WC_Order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'woo-personalization' ) . '</p>';
			return;
		}

		if ( ! WCP_Cart_Order::order_has_personalization( $order ) ) {
			echo '<p>' . esc_html__( 'No personalized items in this order.', 'woo-personalization' ) . '</p>';
			return;
		}

		$file_count = self::count_order_files( $order );
		if ( $file_count < 1 ) {
			echo '<p class="wcp-admin-warning">' . esc_html__( 'Personalization files are not ready yet.', 'woo-personalization' ) . '</p>';
			return;
		}

		$url = self::get_zip_url( $order->get_id() );
		if ( ! $url ) {
			return;
		}

		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of files */
				_n( '%d design file ready.', '%d design files ready.', $file_count, 'woo-personalization' ),
				$file_count
			)
		) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Download all as ZIP', 'woo-personalization' ) . '</a></p>';
	}

	/**
	 * Count downloadable personalization files on an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return int
	 */
	private static function count_order_files( $order ) {
		$count = 0;

		foreach ( self::collect_order_files( $order ) as $file ) {
			if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Collect file paths for personalized order items.
	 *
	 * @param WC_Order $order Order object.
	 * @return array<int, array{path: string, name: string}>
	 */
	private static function collect_order_files( $order ) {
		$files = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
				continue;
			}

			$paths = WCP_Cart_Order::resolve_item_file_paths( $item );
			$label = sanitize_file_name( $item->get_name() );
			$slug  = $label ? $label : 'item-' . absint( $item_id );

			if ( ! empty( $paths['original'] ) && file_exists( $paths['original'] ) ) {
				$files[] = array(
					'path' => $paths['original'],
					'name' => $slug . '-original-' . absint( $item_id ) . '.' . pathinfo( $paths['original'], PATHINFO_EXTENSION ),
				);
			}

			if ( ! empty( $paths['mockup'] ) && file_exists( $paths['mockup'] ) ) {
				$files[] = array(
					'path' => $paths['mockup'],
					'name' => $slug . '-mockup-' . absint( $item_id ) . '.png',
				);
			}
		}

		return $files;
	}

	/**
	 * Build secure ZIP download URL.
	 *
	 * @param int $order_id Order ID.
	 * @return string|false
	 */
	public static function get_zip_url( $order_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => self::ZIP_ACTION,
					'order_id' => absint( $order_id ),
				),
				admin_url( 'admin-ajax.php' )
			),
			'wcp_zip_' . absint( $order_id ),
			'nonce'
		);
	}

	/**
	 * Stream ZIP archive to browser.
	 */
	public static function handle_download() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		if ( ! $order_id ) {
			wp_die( esc_html__( 'Invalid download request.', 'woo-personalization' ), 400 );
		}

		check_ajax_referer( 'wcp_zip_' . $order_id, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to download these files.', 'woo-personalization' ), 403 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'woo-personalization' ), 404 );
		}

		$files = self::collect_order_files( $order );
		if ( empty( $files ) ) {
			wp_die( esc_html__( 'No personalization files found for this order.', 'woo-personalization' ), 404 );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'ZIP extension is not available on this server.', 'woo-personalization' ), 500 );
		}

		$zip_path = trailingslashit( get_temp_dir() ) . 'wcp-order-' . $order_id . '-' . wp_generate_password( 8, false, false ) . '.zip';
		$zip      = new ZipArchive();

		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Could not create ZIP archive.', 'woo-personalization' ), 500 );
		}

		foreach ( $files as $file ) {
			if ( empty( $file['path'] ) || ! file_exists( $file['path'] ) ) {
				continue;
			}

			$zip->addFile( $file['path'], $file['name'] );
		}

		$zip->close();

		if ( ! file_exists( $zip_path ) ) {
			wp_die( esc_html__( 'ZIP archive could not be prepared.', 'woo-personalization' ), 500 );
		}

		$filename = 'order-' . $order_id . '-personalization.zip';

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );

		readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		wp_delete_file( $zip_path );
		exit;
	}
}

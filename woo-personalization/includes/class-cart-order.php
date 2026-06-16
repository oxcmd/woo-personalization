<?php
/**
 * Cart and order personalization persistence.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Cart_Order
 */
class WCP_Cart_Order {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_order_line_item_meta' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'finalize_order_files' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_thumbnail', array( __CLASS__, 'cart_item_thumbnail' ), 10, 3 );
	}

	/**
	 * Attach personalization data to cart item.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id     Product ID.
	 * @param int                  $variation_id   Variation ID.
	 * @return array<string, mixed>
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		unset( $variation_id );

		if ( ! WCP_Product_Settings::is_enabled( $product_id ) ) {
			return $cart_item_data;
		}

		$token = isset( $_POST['wcp_upload_token'] ) ? sanitize_key( wp_unslash( $_POST['wcp_upload_token'] ) ) : '';
		$check_id = WCP_Product_Settings::resolve_product_id( $product_id );
		$data     = WCP_Upload_Handler::get_upload_data( $token );

		if ( empty( $token ) || ! $data || (int) $data['product_id'] !== $check_id ) {
			return $cart_item_data;
		}

		$cart_item_data['wcp_personalization'] = array(
			'token'         => $token,
			'template_id'   => (int) $data['template_id'],
			'print_area'    => $data['print_area'],
			'default_fit'   => $data['default_fit'],
			'original_file' => $data['original_file'],
			'mockup_file'   => $data['mockup_file'],
			'unique_key'    => md5( $token . microtime( true ) ),
		);

		return $cart_item_data;
	}

	/**
	 * Display personalization in cart/checkout.
	 *
	 * @param array<int, array<string, string>> $item_data Item data rows.
	 * @param array<string, mixed>              $cart_item Cart item.
	 * @return array<int, array<string, string>>
	 */
	public static function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['wcp_personalization'] ) ) {
			return $item_data;
		}

		$personalization = $cart_item['wcp_personalization'];

		$item_data[] = array(
			'key'   => __( 'Custom design', 'woo-personalization' ),
			'value' => esc_html__( 'Personalized mockup attached', 'woo-personalization' ),
		);

		return $item_data;
	}

	/**
	 * Replace cart thumbnail with mockup when available.
	 *
	 * @param string               $thumbnail HTML thumbnail.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @param string               $cart_key  Cart key.
	 * @return string
	 */
	public static function cart_item_thumbnail( $thumbnail, $cart_item, $cart_key ) {
		unset( $cart_key );

		if ( empty( $cart_item['wcp_personalization'] ) ) {
			return $thumbnail;
		}

		$personalization = $cart_item['wcp_personalization'];
		$mockup_url      = self::get_temp_file_url( $personalization['token'], $personalization['mockup_file'] );

		if ( ! $mockup_url ) {
			return $thumbnail;
		}

		return '<img src="' . esc_url( $mockup_url ) . '" alt="' . esc_attr__( 'Custom mockup preview', 'woo-personalization' ) . '" class="wcp-cart-mockup-thumb" />';
	}

	/**
	 * Persist personalization meta on order line item.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array<string, mixed>  $values        Cart values.
	 * @param WC_Order              $order         Order object.
	 */
	public static function add_order_line_item_meta( $item, $cart_item_key, $values, $order ) {
		unset( $cart_item_key, $order );

		if ( empty( $values['wcp_personalization'] ) ) {
			return;
		}

		$personalization = $values['wcp_personalization'];

		$item->add_meta_data( '_wcp_upload_token', $personalization['token'], true );
		$item->add_meta_data( '_wcp_template_id', (int) $personalization['template_id'], true );
		$item->add_meta_data( '_wcp_print_area', wp_json_encode( $personalization['print_area'] ), true );
		$item->add_meta_data( '_wcp_original_file', sanitize_file_name( $personalization['original_file'] ), true );
		$item->add_meta_data( '_wcp_mockup_file', sanitize_file_name( $personalization['mockup_file'] ), true );
		$item->add_meta_data( '_wcp_personalized', 'yes', true );
	}

	/**
	 * Move temp files to permanent order directory after checkout.
	 *
	 * @param int      $order_id   Order ID.
	 * @param array    $posted_data Posted checkout data.
	 * @param WC_Order $order      Order object.
	 */
	public static function finalize_order_files( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$order_dir = WCP_Plugin::get_order_dir( $order->get_id() );

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			if ( 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
				continue;
			}

			$token         = sanitize_key( $item->get_meta( '_wcp_upload_token' ) );
			$original_name = sanitize_file_name( $item->get_meta( '_wcp_original_file' ) );
			$mockup_name   = sanitize_file_name( $item->get_meta( '_wcp_mockup_file' ) );

			if ( empty( $token ) ) {
				continue;
			}

			$temp_dir = WCP_Upload_Handler::get_token_dir( $token );
			$item_dir = trailingslashit( $order_dir ) . 'item-' . absint( $item_id );

			if ( ! file_exists( $item_dir ) ) {
				wp_mkdir_p( $item_dir );
			}

			$original_src = trailingslashit( $temp_dir ) . $original_name;
			$mockup_src   = trailingslashit( $temp_dir ) . $mockup_name;

			$original_dest = trailingslashit( $item_dir ) . $original_name;
			$mockup_dest   = trailingslashit( $item_dir ) . $mockup_name;

			if ( file_exists( $original_src ) ) {
				copy( $original_src, $original_dest );
			}

			if ( file_exists( $mockup_src ) ) {
				copy( $mockup_src, $mockup_dest );
			}

			$item->update_meta_data( '_wcp_order_original_path', $original_dest );
			$item->update_meta_data( '_wcp_order_mockup_path', $mockup_dest );
			$item->save();

			delete_transient( 'wcp_upload_' . $token );
			WCP_Upload_Handler::delete_directory( $temp_dir );
		}
	}

	/**
	 * Build public URL for temp file.
	 *
	 * @param string $token     Upload token.
	 * @param string $filename  File name.
	 * @return string|false
	 */
	public static function get_temp_file_url( $token, $filename ) {
		$path = trailingslashit( WCP_Upload_Handler::get_token_dir( $token ) ) . sanitize_file_name( $filename );

		if ( ! file_exists( $path ) ) {
			return false;
		}

		return trailingslashit( WCP_Plugin::get_upload_base_url() ) . WCP_TEMP_DIR . '/' . rawurlencode( sanitize_key( $token ) ) . '/' . rawurlencode( sanitize_file_name( $filename ) );
	}

	/**
	 * Get order item mockup URL for admin display.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string|false
	 */
	public static function get_order_mockup_url( $item ) {
		$path = $item->get_meta( '_wcp_order_mockup_path' );
		if ( $path && file_exists( $path ) ) {
			return self::path_to_admin_url( $path );
		}

		return false;
	}

	/**
	 * Convert absolute path under uploads to URL.
	 *
	 * @param string $path Absolute file path.
	 * @return string|false
	 */
	public static function path_to_admin_url( $path ) {
		$upload = wp_upload_dir();
		if ( 0 !== strpos( $path, $upload['basedir'] ) ) {
			return false;
		}

		$relative = ltrim( substr( $path, strlen( $upload['basedir'] ) ), '/' );

		return trailingslashit( $upload['baseurl'] ) . str_replace( DIRECTORY_SEPARATOR, '/', $relative );
	}
}

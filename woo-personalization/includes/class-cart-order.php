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
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'finalize_order_files_from_store_api' ), 10, 1 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'finalize_order_files_by_id' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'finalize_order_files_by_id' ), 10, 1 );
		add_action( 'woocommerce_order_status_on-hold', array( __CLASS__, 'finalize_order_files_by_id' ), 10, 1 );
		add_filter( 'woocommerce_cart_item_thumbnail', array( __CLASS__, 'cart_item_thumbnail' ), 10, 3 );
	}

	/**
	 * Store API / block checkout callback.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function finalize_order_files_from_store_api( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		self::finalize_order_files( $order->get_id(), array(), $order );
	}

	/**
	 * Finalize files when order reaches a persisted status.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function finalize_order_files_by_id( $order_id ) {
		self::finalize_order_files( $order_id, array(), wc_get_order( $order_id ) );
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

		$token    = isset( $_POST['wcp_upload_token'] ) ? sanitize_key( wp_unslash( $_POST['wcp_upload_token'] ) ) : '';
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

		$item->add_meta_data( '_wcp_upload_token', sanitize_key( $personalization['token'] ), true );
		$item->add_meta_data( '_wcp_template_id', (int) $personalization['template_id'], true );
		$item->add_meta_data( '_wcp_print_area', wp_json_encode( $personalization['print_area'] ), true );
		$item->add_meta_data( '_wcp_original_file', sanitize_file_name( $personalization['original_file'] ), true );
		$item->add_meta_data( '_wcp_mockup_file', sanitize_file_name( $personalization['mockup_file'] ), true );
		$item->add_meta_data( '_wcp_personalized', 'yes', true );
	}

	/**
	 * Move temp files to permanent order directory after checkout.
	 *
	 * @param int           $order_id    Order ID.
	 * @param array         $posted_data Posted checkout data.
	 * @param WC_Order|null $order       Order object.
	 */
	public static function finalize_order_files( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			if ( 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
				continue;
			}

			self::finalize_item_files( $order, $item );
		}
	}

	/**
	 * Copy personalization files for a single order line item.
	 *
	 * @param WC_Order              $order Order object.
	 * @param WC_Order_Item_Product $item  Order item.
	 * @return bool
	 */
	public static function finalize_item_files( $order, $item ) {
		$token         = sanitize_key( (string) $item->get_meta( '_wcp_upload_token' ) );
		$original_name = sanitize_file_name( (string) $item->get_meta( '_wcp_original_file' ) );
		$mockup_name   = sanitize_file_name( (string) $item->get_meta( '_wcp_mockup_file' ) );

		if ( empty( $token ) || empty( $original_name ) || empty( $mockup_name ) ) {
			return false;
		}

		$existing_original = (string) $item->get_meta( '_wcp_order_original_path' );
		$existing_mockup   = (string) $item->get_meta( '_wcp_order_mockup_path' );

		if ( $existing_original && file_exists( $existing_original ) && $existing_mockup && file_exists( $existing_mockup ) ) {
			return true;
		}

		$temp_dir  = WCP_Upload_Handler::resolve_token_dir( $token );
		$order_dir = WCP_Plugin::get_order_dir( $order->get_id() );
		$item_dir  = trailingslashit( $order_dir ) . 'item-' . absint( $item->get_id() );

		if ( ! file_exists( $item_dir ) ) {
			wp_mkdir_p( $item_dir );
		}

		$original_src = trailingslashit( $temp_dir ) . $original_name;
		$mockup_src   = trailingslashit( $temp_dir ) . $mockup_name;
		$original_dest = trailingslashit( $item_dir ) . $original_name;
		$mockup_dest   = trailingslashit( $item_dir ) . $mockup_name;

		$copied_original = self::copy_file_if_exists( $original_src, $original_dest );
		$copied_mockup   = self::copy_file_if_exists( $mockup_src, $mockup_dest );

		if ( ! $copied_original && ! $copied_mockup ) {
			return false;
		}

		if ( $copied_original ) {
			$item->update_meta_data( '_wcp_order_original_path', $original_dest );
		}

		if ( $copied_mockup ) {
			$item->update_meta_data( '_wcp_order_mockup_path', $mockup_dest );
		}

		$item->save();

		if ( $copied_original && $copied_mockup ) {
			delete_transient( 'wcp_upload_' . $token );
			WCP_Upload_Handler::delete_directory( $temp_dir );
		}

		return $copied_original || $copied_mockup;
	}

	/**
	 * Resolve stored file paths for an order item, finalizing if needed.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return array{original: string, mockup: string}
	 */
	public static function resolve_item_file_paths( $item ) {
		$paths = array(
			'original' => '',
			'mockup'   => '',
		);

		if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
			return $paths;
		}

		$order = wc_get_order( $item->get_order_id() );
		if ( $order ) {
			self::finalize_item_files( $order, $item );
		}

		$original = (string) $item->get_meta( '_wcp_order_original_path' );
		$mockup   = (string) $item->get_meta( '_wcp_order_mockup_path' );

		if ( $original && file_exists( $original ) ) {
			$paths['original'] = $original;
		}

		if ( $mockup && file_exists( $mockup ) ) {
			$paths['mockup'] = $mockup;
		}

		if ( $paths['original'] && $paths['mockup'] ) {
			return $paths;
		}

		$token         = sanitize_key( (string) $item->get_meta( '_wcp_upload_token' ) );
		$original_name = sanitize_file_name( (string) $item->get_meta( '_wcp_original_file' ) );
		$mockup_name   = sanitize_file_name( (string) $item->get_meta( '_wcp_mockup_file' ) );
		$temp_dir      = WCP_Upload_Handler::resolve_token_dir( $token );

		if ( empty( $paths['original'] ) && $token && $original_name ) {
			$temp_original = trailingslashit( $temp_dir ) . $original_name;
			if ( file_exists( $temp_original ) ) {
				$paths['original'] = $temp_original;
			}
		}

		if ( empty( $paths['mockup'] ) && $token && $mockup_name ) {
			$temp_mockup = trailingslashit( $temp_dir ) . $mockup_name;
			if ( file_exists( $temp_mockup ) ) {
				$paths['mockup'] = $temp_mockup;
			}
		}

		return $paths;
	}

	/**
	 * Copy a file when the source exists.
	 *
	 * @param string $source      Source path.
	 * @param string $destination Destination path.
	 * @return bool
	 */
	private static function copy_file_if_exists( $source, $destination ) {
		if ( ! file_exists( $source ) ) {
			return false;
		}

		$dir = dirname( $destination );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		if ( file_exists( $destination ) ) {
			wp_delete_file( $destination );
		}

		return copy( $source, $destination );
	}

	/**
	 * Build public URL for temp file.
	 *
	 * @param string $token    Upload token.
	 * @param string $filename File name.
	 * @return string|false
	 */
	public static function get_temp_file_url( $token, $filename ) {
		$token    = sanitize_key( $token );
		$temp_dir = WCP_Upload_Handler::resolve_token_dir( $token );
		$path     = trailingslashit( $temp_dir ) . sanitize_file_name( $filename );

		if ( ! file_exists( $path ) ) {
			return false;
		}

		return self::path_to_public_url( $path );
	}

	/**
	 * Whether an order contains at least one personalized line item.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public static function order_has_personalization( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product && 'yes' === $item->get_meta( '_wcp_personalized' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the first available mockup URL from personalized items in an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|false
	 */
	public static function get_first_order_mockup_url( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || 'yes' !== $item->get_meta( '_wcp_personalized' ) ) {
				continue;
			}

			$mockup_url = self::get_order_mockup_url( $item );
			if ( $mockup_url ) {
				return $mockup_url;
			}
		}

		return false;
	}

	/**
	 * Get order item mockup URL for admin display.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string|false
	 */
	public static function get_order_mockup_url( $item ) {
		$paths = self::resolve_item_file_paths( $item );

		if ( empty( $paths['mockup'] ) ) {
			return false;
		}

		return self::path_to_public_url( $paths['mockup'] );
	}

	/**
	 * Get order item original file path for secure download.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string
	 */
	public static function get_order_original_path( $item ) {
		$paths = self::resolve_item_file_paths( $item );

		return $paths['original'];
	}

	/**
	 * Get order item mockup file path for secure download.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return string
	 */
	public static function get_order_mockup_path( $item ) {
		$paths = self::resolve_item_file_paths( $item );

		return $paths['mockup'];
	}

	/**
	 * Convert absolute path under uploads to URL.
	 *
	 * @param string $path Absolute file path.
	 * @return string|false
	 */
	public static function path_to_public_url( $path ) {
		if ( ! $path || ! file_exists( $path ) ) {
			return false;
		}

		$upload = wp_upload_dir();
		if ( 0 !== strpos( wp_normalize_path( $path ), wp_normalize_path( $upload['basedir'] ) ) ) {
			return false;
		}

		$relative = ltrim( substr( wp_normalize_path( $path ), strlen( wp_normalize_path( $upload['basedir'] ) ) ), '/' );

		return trailingslashit( $upload['baseurl'] ) . $relative;
	}
}

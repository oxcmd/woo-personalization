<?php
/**
 * WooCommerce Blocks / Store API cart integration.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Store_Api
 */
class WCP_Store_Api {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_store_api_cart_item_images', array( __CLASS__, 'cart_item_images' ), 10, 3 );
	}

	/**
	 * Replace block cart item image with mockup preview when available.
	 *
	 * @param array<string, mixed> $product_images Product images array.
	 * @param array<string, mixed> $cart_item      Cart item data.
	 * @param string               $cart_item_key  Cart item key.
	 * @return array<string, mixed>
	 */
	public static function cart_item_images( $product_images, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		if ( empty( $cart_item['wcp_personalization'] ) ) {
			return $product_images;
		}

		$personalization = $cart_item['wcp_personalization'];
		$mockup_url      = WCP_Cart_Order::get_temp_file_url( $personalization['token'], $personalization['mockup_file'] );

		if ( ! $mockup_url ) {
			return $product_images;
		}

		return array(
			(object) array(
				'id'        => 0,
				'src'       => $mockup_url,
				'thumbnail' => $mockup_url,
				'srcset'    => '',
				'sizes'     => '',
				'name'      => __( 'Custom mockup preview', 'woo-personalization' ),
				'alt'       => __( 'Custom mockup preview', 'woo-personalization' ),
			),
		);
	}
}

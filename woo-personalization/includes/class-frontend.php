<?php
/**
 * Product page personalization UI.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Frontend
 */
class WCP_Frontend {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_personalizer' ), 15 );
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'render_setup_notice' ), 5 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets on product pages.
	 */
	public static function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$config = WCP_Product_Settings::get_frontend_config( $product->get_id() );
		if ( ! $config ) {
			return;
		}

		wp_enqueue_style(
			'wcp-product-personalizer',
			WCP_PLUGIN_URL . 'assets/css/product-personalizer.css',
			array(),
			WCP_VERSION
		);

		wp_enqueue_script(
			'wcp-product-personalizer',
			WCP_PLUGIN_URL . 'assets/js/product-personalizer.js',
			array( 'jquery' ),
			WCP_VERSION,
			true
		);

		wp_localize_script(
			'wcp-product-personalizer',
			'wcpPersonalizer',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wcp_upload' ),
				'productId' => $product->get_id(),
				'config'    => $config,
				'i18n'      => array(
					'uploadLabel'    => __( 'Upload your design', 'woo-personalization' ),
					'uploading'      => __( 'Uploading...', 'woo-personalization' ),
					'uploadSuccess'  => __( 'Design uploaded. Preview updated.', 'woo-personalization' ),
					'uploadError'    => __( 'Upload failed. Please try again.', 'woo-personalization' ),
					'remove'         => __( 'Remove design', 'woo-personalization' ),
					'required'       => __( 'Please upload a design before adding to cart.', 'woo-personalization' ),
					'invalidType'    => __( 'Please choose a JPG, PNG, or WebP image.', 'woo-personalization' ),
					'lowDpiWarning'  => __( 'Low resolution image', 'woo-personalization' ),
					'positionHint'   => __( 'Drag to reposition and use the slider to resize your design.', 'woo-personalization' ),
				),
			)
		);
	}

	/**
	 * Show setup notice when personalization is enabled but misconfigured.
	 */
	public static function render_setup_notice() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$message = WCP_Product_Settings::get_setup_issue_message( $product->get_id() );
		if ( ! $message ) {
			return;
		}

		wc_print_notice( $message, 'notice' );
	}

	/**
	 * Render personalizer UI.
	 */
	public static function render_personalizer() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$config = WCP_Product_Settings::get_frontend_config( $product->get_id() );
		if ( ! $config ) {
			return;
		}

		include WCP_PLUGIN_DIR . 'templates/product-personalizer.php';
	}

	/**
	 * Validate personalization before add to cart.
	 *
	 * @param bool $passed     Validation passed.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity.
	 * @return bool
	 */
	public static function validate_add_to_cart( $passed, $product_id, $quantity ) {
		unset( $quantity );

		if ( ! WCP_Product_Settings::is_enabled( $product_id ) ) {
			return $passed;
		}

		$token = isset( $_POST['wcp_upload_token'] ) ? sanitize_key( wp_unslash( $_POST['wcp_upload_token'] ) ) : '';
		$check_id = WCP_Product_Settings::resolve_product_id( $product_id );
		$data     = WCP_Upload_Handler::get_upload_data( $token );

		if ( empty( $token ) || ! $data || (int) $data['product_id'] !== $check_id ) {
			wc_add_notice( __( 'Please upload your design image before adding this product to cart.', 'woo-personalization' ), 'error' );
			return false;
		}

		return $passed;
	}
}

<?php
/**
 * WooCommerce product personalization settings tab.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Product_Settings
 */
class WCP_Product_Settings {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_product_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add product data tab.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public static function add_product_tab( $tabs ) {
		$tabs['wcp_personalization'] = array(
			'label'    => __( 'Personalization', 'woo-personalization' ),
			'target'   => 'wcp_personalization_panel',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * Enqueue admin assets on product edit screen.
	 *
	 * @param string $hook Admin hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_style(
			'wcp-admin-template',
			WCP_PLUGIN_URL . 'assets/css/admin-template.css',
			array(),
			WCP_VERSION
		);
		wp_enqueue_script(
			'wcp-print-area-editor',
			WCP_PLUGIN_URL . 'assets/js/admin-print-area-editor.js',
			array( 'jquery' ),
			WCP_VERSION,
			true
		);
		wp_enqueue_script(
			'wcp-admin-product-panel',
			WCP_PLUGIN_URL . 'assets/js/admin-product-panel.js',
			array( 'jquery', 'wcp-print-area-editor' ),
			WCP_VERSION,
			true
		);
		wp_localize_script(
			'wcp-admin-product-panel',
			'wcpProductPanel',
			array(
				'templates' => WCP_Template_CPT::get_templates_admin_config(),
			)
		);
	}

	/**
	 * Render product panel.
	 */
	public static function render_product_panel() {
		global $post;

		$enabled    = 'yes' === get_post_meta( $post->ID, '_wcp_enabled', true );
		$template_id = (int) get_post_meta( $post->ID, '_wcp_template_id', true );
		$override   = get_post_meta( $post->ID, '_wcp_print_area_override', true );
		$override   = is_array( $override ) ? WCP_Plugin::sanitize_print_area( $override ) : WCP_Plugin::default_print_area();
		$templates  = WCP_Template_CPT::get_template_options();
		$preview    = $template_id ? WCP_Template_CPT::get_base_image_url( $template_id ) : '';

		include WCP_PLUGIN_DIR . 'templates/admin-product-panel.php';
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save_product_meta( $post_id ) {
		$enabled = isset( $_POST['_wcp_enabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wcp_enabled', $enabled );

		$template_id = isset( $_POST['_wcp_template_id'] ) ? absint( $_POST['_wcp_template_id'] ) : 0;
		update_post_meta( $post_id, '_wcp_template_id', $template_id );

		if ( 'yes' === $enabled && $template_id <= 0 ) {
			WC_Admin_Meta_Boxes::add_error(
				__( 'Personalization is enabled but no mockup template was selected. The upload UI will not appear until you choose one.', 'woo-personalization' )
			);
		}

		$use_override = isset( $_POST['_wcp_use_print_override'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wcp_use_print_override', $use_override );

		if ( 'yes' === $use_override ) {
			$override = array(
				'x'      => isset( $_POST['_wcp_override_x'] ) ? (float) wp_unslash( $_POST['_wcp_override_x'] ) : 25,
				'y'      => isset( $_POST['_wcp_override_y'] ) ? (float) wp_unslash( $_POST['_wcp_override_y'] ) : 28,
				'width'  => isset( $_POST['_wcp_override_width'] ) ? (float) wp_unslash( $_POST['_wcp_override_width'] ) : 50,
				'height' => isset( $_POST['_wcp_override_height'] ) ? (float) wp_unslash( $_POST['_wcp_override_height'] ) : 45,
			);
			update_post_meta( $post_id, '_wcp_print_area_override', WCP_Plugin::sanitize_print_area( $override ) );
		} else {
			delete_post_meta( $post_id, '_wcp_print_area_override' );
		}
	}

	/**
	 * Resolve parent product ID for variations.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return int
	 */
	public static function resolve_product_id( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variation' ) ) {
			return (int) $product->get_parent_id();
		}

		return (int) $product_id;
	}

	/**
	 * Check if product has personalization enabled.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_enabled( $product_id ) {
		$product_id = self::resolve_product_id( $product_id );

		if ( 'yes' !== get_post_meta( $product_id, '_wcp_enabled', true ) ) {
			return false;
		}

		$template_id = (int) get_post_meta( $product_id, '_wcp_template_id', true );

		return $template_id > 0 && WCP_Template_CPT::get_base_image_path( $template_id );
	}

	/**
	 * Get assigned template ID for product.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public static function get_template_id( $product_id ) {
		return (int) get_post_meta( self::resolve_product_id( $product_id ), '_wcp_template_id', true );
	}

	/**
	 * Get product personalization config for frontend.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_frontend_config( $product_id ) {
		$product_id = self::resolve_product_id( $product_id );

		if ( 'yes' !== get_post_meta( $product_id, '_wcp_enabled', true ) ) {
			return null;
		}

		$template_id = self::get_template_id( $product_id );
		if ( $template_id <= 0 ) {
			return null;
		}

		$base_url = WCP_Template_CPT::get_base_image_url( $template_id );

		if ( ! $base_url ) {
			return null;
		}

		return array(
			'template_id' => $template_id,
			'base_url'    => $base_url,
			'print_area'  => WCP_Template_CPT::get_effective_print_area( $product_id, $template_id ),
			'default_fit' => WCP_Template_CPT::get_default_fit( $template_id ),
		);
	}

	/**
	 * Get admin-facing setup issue message for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return string|null
	 */
	public static function get_setup_issue_message( $product_id ) {
		$product_id = self::resolve_product_id( $product_id );

		if ( 'yes' !== get_post_meta( $product_id, '_wcp_enabled', true ) ) {
			return null;
		}

		$template_id = self::get_template_id( $product_id );
		if ( $template_id <= 0 ) {
			return __( 'Personalization is enabled but no mockup template is selected. Edit the product and choose a template in the Personalization tab.', 'woo-personalization' );
		}

		if ( ! WCP_Template_CPT::get_base_image_path( $template_id ) ) {
			return __( 'The selected mockup template has no base image. Edit the template and upload a shirt mockup image.', 'woo-personalization' );
		}

		return null;
	}
}

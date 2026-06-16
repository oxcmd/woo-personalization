<?php
/**
 * Main plugin orchestrator.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Plugin
 */
class WCP_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WCP_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return WCP_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load class files.
	 */
	private function includes() {
		require_once WCP_PLUGIN_DIR . 'includes/class-image-compositor.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-template-cpt.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-product-settings.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-upload-handler.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-cart-order.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-admin-order.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-cleanup.php';
	}

	/**
	 * Register hooks.
	 */
	private function init_hooks() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		WCP_Template_CPT::init();
		WCP_Product_Settings::init();
		WCP_Upload_Handler::init();
		WCP_Frontend::init();
		WCP_Cart_Order::init();
		WCP_Admin_Order::init();
		WCP_Cleanup::init();
	}

	/**
	 * Declare HPOS compatibility.
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCP_PLUGIN_FILE, true );
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woo-personalization', false, dirname( plugin_basename( WCP_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Admin notice when WooCommerce is missing.
	 */
	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Woo Personalization requires WooCommerce to be installed and active.', 'woo-personalization' )
		);
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( WCP_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'Woo Personalization requires WooCommerce. Please install and activate WooCommerce first.', 'woo-personalization' ),
				esc_html__( 'Plugin Activation Error', 'woo-personalization' ),
				array( 'back_link' => true )
			);
		}

		require_once WCP_PLUGIN_DIR . 'includes/class-template-cpt.php';
		require_once WCP_PLUGIN_DIR . 'includes/class-cleanup.php';

		WCP_Template_CPT::register_post_type();
		flush_rewrite_rules();
		WCP_Cleanup::schedule_cleanup();

		$upload_path = self::get_upload_base_path();
		if ( ! file_exists( trailingslashit( $upload_path ) . 'index.php' ) ) {
			file_put_contents( trailingslashit( $upload_path ) . 'index.php', "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
		WCP_Cleanup::unschedule_cleanup();
	}

	/**
	 * Get upload base directory path.
	 *
	 * @return string
	 */
	public static function get_upload_base_path() {
		$upload = wp_upload_dir();
		$path   = trailingslashit( $upload['basedir'] ) . WCP_UPLOAD_DIR;

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}

	/**
	 * Get upload base URL.
	 *
	 * @return string
	 */
	public static function get_upload_base_url() {
		$upload = wp_upload_dir();

		return trailingslashit( $upload['baseurl'] ) . WCP_UPLOAD_DIR;
	}

	/**
	 * Get temp directory for session uploads.
	 *
	 * @param string $token Session token.
	 * @return string
	 */
	public static function get_temp_dir( $token ) {
		$path = trailingslashit( self::get_upload_base_path() ) . WCP_TEMP_DIR . '/' . sanitize_file_name( $token );

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}

	/**
	 * Get order files directory.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function get_order_dir( $order_id ) {
		$path = trailingslashit( self::get_upload_base_path() ) . WCP_ORDERS_DIR . '/' . absint( $order_id );

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}

	/**
	 * Default print area percentages.
	 *
	 * @return array<string, float>
	 */
	public static function default_print_area() {
		return array(
			'x'      => 25.0,
			'y'      => 28.0,
			'width'  => 50.0,
			'height' => 45.0,
		);
	}

	/**
	 * Sanitize print area array.
	 *
	 * @param array<string, mixed>|null $area Raw print area.
	 * @return array<string, float>
	 */
	public static function sanitize_print_area( $area ) {
		$defaults = self::default_print_area();
		$area     = is_array( $area ) ? $area : array();

		return array(
			'x'      => min( 100, max( 0, (float) ( $area['x'] ?? $defaults['x'] ) ) ),
			'y'      => min( 100, max( 0, (float) ( $area['y'] ?? $defaults['y'] ) ) ),
			'width'  => min( 100, max( 1, (float) ( $area['width'] ?? $defaults['width'] ) ) ),
			'height' => min( 100, max( 1, (float) ( $area['height'] ?? $defaults['height'] ) ) ),
		);
	}
}

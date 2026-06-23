<?php
/**
 * Plugin-wide settings (WooCommerce → Settings → Personalization).
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Settings
 */
class WCP_Settings {

	const OPTION_MIN_DPI        = 'wcp_min_recommended_dpi';
	const OPTION_DPI_WARNING      = 'wcp_enable_dpi_warning';
	const OPTION_MAX_UPLOAD_MB    = 'wcp_max_upload_mb';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_wcp_personalization', array( __CLASS__, 'render_settings' ) );
		add_action( 'woocommerce_update_options_wcp_personalization', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Add Personalization tab to WooCommerce settings.
	 *
	 * @param array<string, string> $tabs Existing tabs.
	 * @return array<string, string>
	 */
	public static function add_settings_tab( $tabs ) {
		$tabs['wcp_personalization'] = __( 'Personalization', 'woo-personalization' );
		return $tabs;
	}

	/**
	 * Output settings fields.
	 */
	public static function render_settings() {
		woocommerce_admin_fields( self::get_settings_fields() );
	}

	/**
	 * Save settings fields.
	 */
	public static function save_settings() {
		woocommerce_update_options( self::get_settings_fields() );
	}

	/**
	 * Settings field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_settings_fields() {
		return array(
			array(
				'title' => __( 'Upload & print quality', 'woo-personalization' ),
				'type'  => 'title',
				'desc'  => __( 'Control upload limits and low-resolution warnings shown to customers.', 'woo-personalization' ),
				'id'    => 'wcp_settings_upload',
			),
			array(
				'title'    => __( 'Max upload size (MB)', 'woo-personalization' ),
				'desc'     => __( 'Maximum image upload size for customer designs. Uses plugin default when empty.', 'woo-personalization' ),
				'id'       => self::OPTION_MAX_UPLOAD_MB,
				'type'     => 'number',
				'default'  => 5,
				'css'      => 'width:80px;',
				'custom_attributes' => array(
					'min'  => '1',
					'max'  => '20',
					'step' => '1',
				),
			),
			array(
				'title'   => __( 'Recommended minimum DPI', 'woo-personalization' ),
				'desc'    => __( 'Used to estimate print quality relative to the mockup print area.', 'woo-personalization' ),
				'id'      => self::OPTION_MIN_DPI,
				'type'    => 'number',
				'default' => 150,
				'css'     => 'width:80px;',
				'custom_attributes' => array(
					'min'  => '72',
					'max'  => '600',
					'step' => '1',
				),
			),
			array(
				'title'   => __( 'Low-resolution warning', 'woo-personalization' ),
				'desc'    => __( 'Show a non-blocking warning when an upload may look blurry in print.', 'woo-personalization' ),
				'id'      => self::OPTION_DPI_WARNING,
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcp_settings_upload',
			),
		);
	}

	/**
	 * Whether DPI warnings are enabled.
	 *
	 * @return bool
	 */
	public static function is_dpi_warning_enabled() {
		return 'yes' === get_option( self::OPTION_DPI_WARNING, 'yes' );
	}

	/**
	 * Recommended minimum effective DPI.
	 *
	 * @return int
	 */
	public static function get_min_recommended_dpi() {
		$value = absint( get_option( self::OPTION_MIN_DPI, 150 ) );
		return max( 72, min( 600, $value ) );
	}

	/**
	 * Max upload size in bytes.
	 *
	 * @return int
	 */
	public static function get_max_upload_bytes() {
		$mb = absint( get_option( self::OPTION_MAX_UPLOAD_MB, 5 ) );
		if ( $mb < 1 ) {
			return WCP_MAX_UPLOAD_BYTES;
		}

		return min( $mb, 20 ) * 1024 * 1024;
	}
}

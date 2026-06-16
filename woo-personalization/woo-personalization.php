<?php
/**
 * Plugin Name:       Woo Personalization
 * Plugin URI:        https://github.com/PolyXGO/woo-personalization
 * Description:       Let customers upload images and preview personalized t-shirt mockups on WooCommerce product pages.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Daily Builder
 * Text Domain:       woo-personalization
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

define( 'WCP_VERSION', '1.0.0' );
define( 'WCP_PLUGIN_FILE', __FILE__ );
define( 'WCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCP_UPLOAD_DIR', 'wcp-uploads' );
define( 'WCP_TEMP_DIR', 'wcp-temp' );
define( 'WCP_ORDERS_DIR', 'wcp-orders' );
define( 'WCP_MAX_UPLOAD_BYTES', 5 * 1024 * 1024 );

require_once WCP_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize plugin after all plugins loaded.
 */
function wcp_init() {
	WCP_Plugin::instance();
}
add_action( 'plugins_loaded', 'wcp_init' );

register_activation_hook( __FILE__, array( 'WCP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WCP_Plugin', 'deactivate' ) );

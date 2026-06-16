<?php
/**
 * Mockup template custom post type and admin meta.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Template_CPT
 */
class WCP_Template_CPT {

	const POST_TYPE = 'wcp_mockup_template';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Mockup Templates', 'woo-personalization' ),
					'singular_name'      => __( 'Mockup Template', 'woo-personalization' ),
					'add_new'            => __( 'Add New', 'woo-personalization' ),
					'add_new_item'       => __( 'Add New Mockup Template', 'woo-personalization' ),
					'edit_item'          => __( 'Edit Mockup Template', 'woo-personalization' ),
					'new_item'           => __( 'New Mockup Template', 'woo-personalization' ),
					'view_item'          => __( 'View Mockup Template', 'woo-personalization' ),
					'search_items'       => __( 'Search Mockup Templates', 'woo-personalization' ),
					'not_found'          => __( 'No mockup templates found.', 'woo-personalization' ),
					'not_found_in_trash' => __( 'No mockup templates found in Trash.', 'woo-personalization' ),
					'menu_name'          => __( 'Mockup Templates', 'woo-personalization' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'woocommerce',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'wcp_template_settings',
			__( 'Mockup Settings', 'woo-personalization' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin assets on template edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		global $post;

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		wp_enqueue_media();
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
			'wcp-admin-template',
			WCP_PLUGIN_URL . 'assets/js/admin-template.js',
			array( 'jquery', 'wcp-print-area-editor' ),
			WCP_VERSION,
			true
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'wcp_save_template_meta', 'wcp_template_nonce' );

		$base_image_id = (int) get_post_meta( $post->ID, '_wcp_base_image_id', true );
		$print_area    = self::get_print_area( $post->ID );
		$side          = get_post_meta( $post->ID, '_wcp_side', true ) ?: 'front';
		$default_fit   = get_post_meta( $post->ID, '_wcp_default_fit', true ) ?: 'cover';
		$image_url     = $base_image_id ? wp_get_attachment_image_url( $base_image_id, 'large' ) : '';

		include WCP_PLUGIN_DIR . 'templates/admin-template-meta.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST['wcp_template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcp_template_nonce'] ) ), 'wcp_save_template_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$base_image_id = isset( $_POST['wcp_base_image_id'] ) ? absint( $_POST['wcp_base_image_id'] ) : 0;
		update_post_meta( $post_id, '_wcp_base_image_id', $base_image_id );

		$print_area = array(
			'x'      => isset( $_POST['wcp_print_x'] ) ? (float) wp_unslash( $_POST['wcp_print_x'] ) : 25,
			'y'      => isset( $_POST['wcp_print_y'] ) ? (float) wp_unslash( $_POST['wcp_print_y'] ) : 28,
			'width'  => isset( $_POST['wcp_print_width'] ) ? (float) wp_unslash( $_POST['wcp_print_width'] ) : 50,
			'height' => isset( $_POST['wcp_print_height'] ) ? (float) wp_unslash( $_POST['wcp_print_height'] ) : 45,
		);
		update_post_meta( $post_id, '_wcp_print_area', WCP_Plugin::sanitize_print_area( $print_area ) );

		$side = isset( $_POST['wcp_side'] ) ? sanitize_key( wp_unslash( $_POST['wcp_side'] ) ) : 'front';
		update_post_meta( $post_id, '_wcp_side', in_array( $side, array( 'front', 'back' ), true ) ? $side : 'front' );

		$fit = isset( $_POST['wcp_default_fit'] ) ? sanitize_key( wp_unslash( $_POST['wcp_default_fit'] ) ) : 'cover';
		update_post_meta( $post_id, '_wcp_default_fit', in_array( $fit, array( 'cover', 'contain' ), true ) ? $fit : 'cover' );
	}

	/**
	 * Get print area for a template.
	 *
	 * @param int $template_id Template post ID.
	 * @return array<string, float>
	 */
	public static function get_print_area( $template_id ) {
		$stored = get_post_meta( $template_id, '_wcp_print_area', true );

		return WCP_Plugin::sanitize_print_area( is_array( $stored ) ? $stored : null );
	}

	/**
	 * Get base image attachment path.
	 *
	 * @param int $template_id Template post ID.
	 * @return string|false
	 */
	public static function get_base_image_path( $template_id ) {
		$attachment_id = (int) get_post_meta( $template_id, '_wcp_base_image_id', true );
		if ( ! $attachment_id ) {
			return false;
		}

		$path = get_attached_file( $attachment_id );

		return $path && file_exists( $path ) ? $path : false;
	}

	/**
	 * Get base image URL.
	 *
	 * @param int $template_id Template post ID.
	 * @return string|false
	 */
	public static function get_base_image_url( $template_id ) {
		$attachment_id = (int) get_post_meta( $template_id, '_wcp_base_image_id', true );
		if ( ! $attachment_id ) {
			return false;
		}

		return wp_get_attachment_image_url( $attachment_id, 'large' ) ?: false;
	}

	/**
	 * Get default fit mode.
	 *
	 * @param int $template_id Template post ID.
	 * @return string
	 */
	public static function get_default_fit( $template_id ) {
		$fit = get_post_meta( $template_id, '_wcp_default_fit', true );

		return 'contain' === $fit ? 'contain' : 'cover';
	}

	/**
	 * Get all published templates for dropdowns.
	 *
	 * @return array<int, string>
	 */
	public static function get_template_options() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$options = array();
		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title;
		}

		return $options;
	}

	/**
	 * Template data for admin visual print-area editor.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_templates_admin_config() {
		$config = array();

		foreach ( array_keys( self::get_template_options() ) as $template_id ) {
			$url = self::get_base_image_url( $template_id );
			if ( ! $url ) {
				continue;
			}

			$config[ $template_id ] = array(
				'url'        => $url,
				'print_area' => self::get_print_area( $template_id ),
			);
		}

		return $config;
	}

	/**
	 * Resolve effective print area for a product.
	 *
	 * @param int $product_id  Product ID.
	 * @param int $template_id Template ID.
	 * @return array<string, float>
	 */
	public static function get_effective_print_area( $product_id, $template_id ) {
		if ( 'yes' === get_post_meta( $product_id, '_wcp_use_print_override', true ) ) {
			$override = get_post_meta( $product_id, '_wcp_print_area_override', true );
			if ( is_array( $override ) ) {
				return WCP_Plugin::sanitize_print_area( $override );
			}
		}

		return self::get_print_area( $template_id );
	}
}

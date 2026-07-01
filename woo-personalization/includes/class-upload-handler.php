<?php
/**
 * AJAX upload handler and temp file management.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Upload_Handler
 */
class WCP_Upload_Handler {

	const AJAX_ACTION        = 'wcp_upload_image';
	const UPDATE_ACTION      = 'wcp_update_design';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_upload' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'handle_upload' ) );
		add_action( 'wp_ajax_' . self::UPDATE_ACTION, array( __CLASS__, 'handle_update_design' ) );
		add_action( 'wp_ajax_nopriv_' . self::UPDATE_ACTION, array( __CLASS__, 'handle_update_design' ) );
	}

	/**
	 * Handle image upload AJAX request.
	 */
	public static function handle_upload() {
		check_ajax_referer( 'wcp_upload', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id || ! WCP_Product_Settings::is_enabled( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Personalization is not enabled for this product.', 'woo-personalization' ) ), 400 );
		}

		if ( empty( $_FILES['wcp_image'] ) || ! is_array( $_FILES['wcp_image'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No image uploaded.', 'woo-personalization' ) ), 400 );
		}

		$file = $_FILES['wcp_image'];
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			wp_send_json_error( array( 'message' => __( 'Upload failed. Please try again.', 'woo-personalization' ) ), 400 );
		}

		if ( (int) $file['size'] > WCP_Settings::get_max_upload_bytes() ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: max file size */
						__( 'File exceeds maximum size of %s.', 'woo-personalization' ),
						size_format( WCP_Settings::get_max_upload_bytes() )
					),
				),
				400
			);
		}

		$validated = self::validate_image_file( $file['tmp_name'], $file['name'] );
		if ( is_wp_error( $validated ) ) {
			wp_send_json_error( array( 'message' => $validated->get_error_message() ), 400 );
		}

		$template_id = WCP_Product_Settings::get_template_id( $product_id );
		$base_path   = WCP_Template_CPT::get_base_image_path( $template_id );
		if ( ! $base_path ) {
			wp_send_json_error( array( 'message' => __( 'Mockup template is misconfigured.', 'woo-personalization' ) ), 500 );
		}

		$token    = sanitize_key( wp_generate_password( 32, false, false ) );
		$temp_dir = WCP_Plugin::get_temp_dir( $token );

		$ext           = $validated['ext'];
		$original_name = 'original.' . $ext;
		$original_path = trailingslashit( $temp_dir ) . $original_name;

		if ( ! move_uploaded_file( $file['tmp_name'], $original_path ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save uploaded file.', 'woo-personalization' ) ), 500 );
		}

		$print_area  = WCP_Template_CPT::get_effective_print_area( $product_id, $template_id );
		$default_fit = WCP_Template_CPT::get_default_fit( $template_id );
		$transform   = self::get_transform_from_request();
		$mockup_path = trailingslashit( $temp_dir ) . 'mockup.png';

		$compositor = new WCP_Image_Compositor();
		$result     = $compositor->composite( $base_path, $original_path, $print_area, $default_fit, $mockup_path, $transform );

		if ( is_wp_error( $result ) ) {
			self::delete_directory( $temp_dir );
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		$meta = array(
			'product_id'    => $product_id,
			'template_id'   => $template_id,
			'print_area'    => $print_area,
			'default_fit'   => $default_fit,
			'original_file' => $original_name,
			'mockup_file'   => 'mockup.png',
			'created'       => time(),
			'design_transform' => $transform,
		);

		file_put_contents( trailingslashit( $temp_dir ) . 'meta.json', wp_json_encode( $meta ) );
		set_transient( 'wcp_upload_' . $token, $meta, DAY_IN_SECONDS );

		$upload_base = WCP_Plugin::get_upload_base_url();
		$response    = array(
			'token'       => $token,
			'preview_url' => trailingslashit( $upload_base ) . WCP_TEMP_DIR . '/' . rawurlencode( $token ) . '/mockup.png',
			'print_area'  => $print_area,
			'default_fit' => $default_fit,
		);

		$dpi = WCP_Dpi_Checker::evaluate( $original_path, $template_id, $print_area );
		if ( is_array( $dpi ) && ! empty( $dpi['is_low'] ) ) {
			$response['dpi_warning']      = $dpi['message'];
			$response['effective_dpi']    = (int) $dpi['effective_dpi'];
		}

		wp_send_json_success( $response );
	}

	/**
	 * Re-composite mockup after customer adjusts position or scale.
	 */
	public static function handle_update_design() {
		check_ajax_referer( 'wcp_upload', 'nonce' );

		$token      = isset( $_POST['token'] ) ? sanitize_key( wp_unslash( $_POST['token'] ) ) : '';
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$transform  = self::get_transform_from_request();

		if ( empty( $token ) || ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid design update request.', 'woo-personalization' ) ), 400 );
		}

		$result = self::regenerate_mockup( $token, $product_id, $transform );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Rebuild mockup.png for an existing upload token.
	 *
	 * @param string               $token      Upload token.
	 * @param int                  $product_id Product ID for validation.
	 * @param array<string, float> $transform  Design transform.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function regenerate_mockup( $token, $product_id, $transform ) {
		$token = sanitize_key( $token );
		$data  = self::get_upload_data( $token );

		if ( ! $data || (int) $data['product_id'] !== (int) $product_id ) {
			return new WP_Error( 'wcp_invalid_token', __( 'Upload session not found or expired.', 'woo-personalization' ) );
		}

		$temp_dir      = self::resolve_token_dir( $token );
		$original_path = trailingslashit( $temp_dir ) . sanitize_file_name( $data['original_file'] );
		$mockup_path   = trailingslashit( $temp_dir ) . 'mockup.png';
		$base_path     = WCP_Template_CPT::get_base_image_path( (int) $data['template_id'] );

		if ( ! $base_path || ! file_exists( $original_path ) ) {
			return new WP_Error( 'wcp_missing_files', __( 'Design files are unavailable. Please upload again.', 'woo-personalization' ) );
		}

		$transform  = WCP_Plugin::sanitize_design_transform( $transform );
		$compositor = new WCP_Image_Compositor();
		$result     = $compositor->composite(
			$base_path,
			$original_path,
			$data['print_area'],
			$data['default_fit'],
			$mockup_path,
			$transform
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data['design_transform'] = $transform;
		file_put_contents( trailingslashit( $temp_dir ) . 'meta.json', wp_json_encode( $data ) );
		set_transient( 'wcp_upload_' . $token, $data, DAY_IN_SECONDS );

		$upload_base = WCP_Plugin::get_upload_base_url();
		$preview_url = trailingslashit( $upload_base ) . WCP_TEMP_DIR . '/' . rawurlencode( $token ) . '/mockup.png';

		return array(
			'token'       => $token,
			'preview_url' => add_query_arg( 'v', (string) time(), $preview_url ),
			'transform'   => $transform,
		);
	}

	/**
	 * Read design transform values from the current request.
	 *
	 * @return array{scale: float, offset_x: float, offset_y: float}
	 */
	private static function get_transform_from_request() {
		$scale = isset( $_POST['scale'] ) ? (float) wp_unslash( $_POST['scale'] ) : 1.0;
		if ( isset( $_POST['wcp_design_scale'] ) ) {
			$scale = (float) wp_unslash( $_POST['wcp_design_scale'] );
			if ( $scale > 2 ) {
				$scale = $scale / 100;
			}
		}

		return WCP_Plugin::sanitize_design_transform(
			array(
				'scale'    => $scale,
				'offset_x' => isset( $_POST['offset_x'] ) ? (float) wp_unslash( $_POST['offset_x'] ) : ( isset( $_POST['wcp_design_offset_x'] ) ? (float) wp_unslash( $_POST['wcp_design_offset_x'] ) : 0 ),
				'offset_y' => isset( $_POST['offset_y'] ) ? (float) wp_unslash( $_POST['offset_y'] ) : ( isset( $_POST['wcp_design_offset_y'] ) ? (float) wp_unslash( $_POST['wcp_design_offset_y'] ) : 0 ),
			)
		);
	}

	/**
	 * Validate uploaded image.
	 *
	 * @param string $tmp_path Temp file path.
	 * @param string $filename Original filename.
	 * @return array<string, string>|WP_Error
	 */
	public static function validate_image_file( $tmp_path, $filename ) {
		$checked = wp_check_filetype_and_ext( $tmp_path, $filename );
		$allowed = array( 'jpg', 'jpeg', 'png', 'webp' );

		if ( empty( $checked['ext'] ) || ! in_array( strtolower( $checked['ext'] ), $allowed, true ) ) {
			return new WP_Error( 'wcp_invalid_type', __( 'Only JPG, PNG, and WebP images are allowed.', 'woo-personalization' ) );
		}

		$size = @getimagesize( $tmp_path );
		if ( false === $size ) {
			return new WP_Error( 'wcp_invalid_image', __( 'The uploaded file is not a valid image.', 'woo-personalization' ) );
		}

		return array(
			'ext'  => strtolower( $checked['ext'] === 'jpeg' ? 'jpg' : $checked['ext'] ),
			'mime' => $checked['type'],
		);
	}

	/**
	 * Get upload data by token.
	 *
	 * @param string $token Upload token.
	 * @return array<string, mixed>|null
	 */
	public static function get_upload_data( $token ) {
		$token = sanitize_key( $token );
		if ( empty( $token ) ) {
			return null;
		}

		$meta = get_transient( 'wcp_upload_' . $token );
		if ( ! is_array( $meta ) ) {
			$meta_path = trailingslashit( self::resolve_token_dir( $token ) ) . 'meta.json';
			if ( file_exists( $meta_path ) ) {
				$decoded = json_decode( file_get_contents( $meta_path ), true );
				$meta    = is_array( $decoded ) ? $decoded : null;
			}
		}

		return is_array( $meta ) ? $meta : null;
	}

	/**
	 * Get temp directory path for token.
	 *
	 * @param string $token Upload token.
	 * @return string
	 */
	public static function get_token_dir( $token ) {
		return WCP_Plugin::get_temp_dir( sanitize_key( $token ) );
	}

	/**
	 * Resolve temp directory even when legacy tokens used mixed case.
	 *
	 * @param string $token Upload token.
	 * @return string
	 */
	public static function resolve_token_dir( $token ) {
		$token   = sanitize_key( $token );
		$default = self::get_token_dir( $token );

		if ( is_dir( $default ) ) {
			return $default;
		}

		$base = trailingslashit( WCP_Plugin::get_upload_base_path() ) . WCP_TEMP_DIR;
		if ( ! is_dir( $base ) ) {
			return $default;
		}

		$directories = glob( trailingslashit( $base ) . '*', GLOB_ONLYDIR );
		if ( ! is_array( $directories ) ) {
			return $default;
		}

		foreach ( $directories as $directory ) {
			if ( strtolower( basename( $directory ) ) === $token ) {
				return $directory;
			}
		}

		return $default;
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory path.
	 */
	public static function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$items = scandir( $dir );
		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( in_array( $item, array( '.', '..' ), true ) ) {
				continue;
			}

			$path = trailingslashit( $dir ) . $item;
			if ( is_dir( $path ) ) {
				self::delete_directory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}

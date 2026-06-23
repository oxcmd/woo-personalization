<?php
/**
 * Print-quality estimation for uploaded designs.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Dpi_Checker
 */
class WCP_Dpi_Checker {

	/**
	 * Evaluate whether an upload may be too low-resolution for the print area.
	 *
	 * @param string               $image_path  Uploaded image path.
	 * @param int                  $template_id Mockup template ID.
	 * @param array<string, float> $print_area  Print area percentages.
	 * @return array{effective_dpi: int, is_low: bool, message: string}|null
	 */
	public static function evaluate( $image_path, $template_id, $print_area ) {
		if ( ! WCP_Settings::is_dpi_warning_enabled() || ! file_exists( $image_path ) ) {
			return null;
		}

		$base_path = WCP_Template_CPT::get_base_image_path( $template_id );
		if ( ! $base_path || ! file_exists( $base_path ) ) {
			return null;
		}

		$upload_size = @getimagesize( $image_path );
		$base_size   = @getimagesize( $base_path );
		if ( ! is_array( $upload_size ) || ! is_array( $base_size ) ) {
			return null;
		}

		$print_area = WCP_Plugin::sanitize_print_area( $print_area );
		$print_w_px = max( 1, (int) round( $base_size[0] * $print_area['width'] / 100 ) );
		$print_h_px = max( 1, (int) round( $base_size[1] * $print_area['height'] / 100 ) );

		$upload_w = max( 1, (int) $upload_size[0] );
		$upload_h = max( 1, (int) $upload_size[1] );

		$ratio_w = $upload_w / $print_w_px;
		$ratio_h = $upload_h / $print_h_px;
		$quality = (int) round( min( $ratio_w, $ratio_h ) * 150 );

		$min_dpi = WCP_Settings::get_min_recommended_dpi();
		$is_low  = $quality < $min_dpi;

		if ( ! $is_low ) {
			return array(
				'effective_dpi' => $quality,
				'is_low'        => false,
				'message'       => '',
			);
		}

		return array(
			'effective_dpi' => $quality,
			'is_low'        => true,
			'message'       => sprintf(
				/* translators: 1: estimated quality number, 2: recommended minimum */
				__( 'This image may look blurry when printed (estimated quality: %1$d, recommended: %2$d+). Consider uploading a higher-resolution file.', 'woo-personalization' ),
				$quality,
				$min_dpi
			),
		);
	}
}

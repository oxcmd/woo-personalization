<?php
/**
 * Server-side image compositing for mockup previews.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Image_Compositor
 */
class WCP_Image_Compositor {

	/**
	 * Composite customer image onto mockup base.
	 *
	 * @param string               $base_path   Base mockup file path.
	 * @param string               $overlay_path Customer upload file path.
	 * @param array<string, float> $print_area  Print area in percentages.
	 * @param string               $fit         cover|contain.
	 * @param string               $output_path Output file path.
	 * @return true|WP_Error
	 */
	public function composite( $base_path, $overlay_path, $print_area, $fit, $output_path ) {
		if ( ! extension_loaded( 'gd' ) ) {
			return new WP_Error( 'wcp_no_gd', __( 'GD extension is required for image processing.', 'woo-personalization' ) );
		}

		$base    = $this->load_image( $base_path );
		$overlay = $this->load_image( $overlay_path );

		if ( is_wp_error( $base ) ) {
			return $base;
		}

		if ( is_wp_error( $overlay ) ) {
			imagedestroy( $base );
			return $overlay;
		}

		$print_area = WCP_Plugin::sanitize_print_area( $print_area );
		$fit        = 'contain' === $fit ? 'contain' : 'cover';

		$bw = imagesx( $base );
		$bh = imagesy( $base );

		$px = (int) round( $print_area['x'] / 100 * $bw );
		$py = (int) round( $print_area['y'] / 100 * $bh );
		$pw = (int) round( $print_area['width'] / 100 * $bw );
		$ph = (int) round( $print_area['height'] / 100 * $bh );

		$ow = imagesx( $overlay );
		$oh = imagesy( $overlay );

		if ( $pw <= 0 || $ph <= 0 || $ow <= 0 || $oh <= 0 ) {
			imagedestroy( $base );
			imagedestroy( $overlay );
			return new WP_Error( 'wcp_invalid_dimensions', __( 'Invalid image dimensions for compositing.', 'woo-personalization' ) );
		}

		$scale = 'cover' === $fit ? max( $pw / $ow, $ph / $oh ) : min( $pw / $ow, $ph / $oh );
		$nw    = max( 1, (int) round( $ow * $scale ) );
		$nh    = max( 1, (int) round( $oh * $scale ) );

		$resized = imagecreatetruecolor( $nw, $nh );
		imagealphablending( $resized, false );
		imagesavealpha( $resized, true );
		$transparent = imagecolorallocatealpha( $resized, 0, 0, 0, 127 );
		imagefill( $resized, 0, 0, $transparent );
		imagecopyresampled( $resized, $overlay, 0, 0, 0, 0, $nw, $nh, $ow, $oh );

		$dx = $px + (int) floor( ( $pw - $nw ) / 2 );
		$dy = $py + (int) floor( ( $ph - $nh ) / 2 );

		imagealphablending( $base, true );
		imagecopy( $base, $resized, $dx, $dy, 0, 0, $nw, $nh );

		imagedestroy( $overlay );
		imagedestroy( $resized );

		$dir = dirname( $output_path );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$result = imagepng( $base, $output_path, 6 );
		imagedestroy( $base );

		if ( ! $result ) {
			return new WP_Error( 'wcp_save_failed', __( 'Failed to save composite image.', 'woo-personalization' ) );
		}

		return true;
	}

	/**
	 * Load image resource from path.
	 *
	 * @param string $path File path.
	 * @return resource|WP_Error
	 */
	private function load_image( $path ) {
		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'wcp_missing_file', __( 'Image file not found.', 'woo-personalization' ) );
		}

		$info = @getimagesize( $path );
		if ( false === $info ) {
			return new WP_Error( 'wcp_invalid_image', __( 'Invalid image file.', 'woo-personalization' ) );
		}

		switch ( $info[2] ) {
			case IMAGETYPE_JPEG:
				$image = @imagecreatefromjpeg( $path );
				break;
			case IMAGETYPE_PNG:
				$image = @imagecreatefrompng( $path );
				break;
			case IMAGETYPE_WEBP:
				if ( function_exists( 'imagecreatefromwebp' ) ) {
					$image = @imagecreatefromwebp( $path );
				} else {
					return new WP_Error( 'wcp_webp_unsupported', __( 'WebP is not supported on this server.', 'woo-personalization' ) );
				}
				break;
			default:
				return new WP_Error( 'wcp_unsupported_type', __( 'Unsupported image type.', 'woo-personalization' ) );
		}

		if ( ! $image ) {
			return new WP_Error( 'wcp_load_failed', __( 'Failed to load image.', 'woo-personalization' ) );
		}

		return $image;
	}
}

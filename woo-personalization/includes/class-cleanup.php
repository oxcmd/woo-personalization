<?php
/**
 * Temp upload cleanup cron.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_Cleanup
 */
class WCP_Cleanup {

	const CRON_HOOK = 'wcp_cleanup_temp_files';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cleanup_temp_files' ) );
	}

	/**
	 * Schedule daily cleanup.
	 */
	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule cleanup on deactivation.
	 */
	public static function unschedule_cleanup() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Delete temp directories older than 24 hours.
	 */
	public static function cleanup_temp_files() {
		$base = trailingslashit( WCP_Plugin::get_upload_base_path() ) . WCP_TEMP_DIR;

		if ( ! is_dir( $base ) ) {
			return;
		}

		$cutoff = time() - DAY_IN_SECONDS;
		$dirs   = glob( trailingslashit( $base ) . '*', GLOB_ONLYDIR );

		if ( ! is_array( $dirs ) ) {
			return;
		}

		foreach ( $dirs as $dir ) {
			$meta_path = trailingslashit( $dir ) . 'meta.json';
			$created   = filemtime( $dir );

			if ( file_exists( $meta_path ) ) {
				$meta = json_decode( (string) file_get_contents( $meta_path ), true );
				if ( is_array( $meta ) && ! empty( $meta['created'] ) ) {
					$created = (int) $meta['created'];
				}
			}

			if ( $created < $cutoff ) {
				$token = basename( $dir );
				delete_transient( 'wcp_upload_' . sanitize_key( $token ) );
				WCP_Upload_Handler::delete_directory( $dir );
			}
		}
	}
}

<?php
/**
 * WooCommerce system status report section.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP_System_Status
 */
class WCP_System_Status {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_system_status_report', array( __CLASS__, 'render_report' ) );
	}

	/**
	 * Output plugin health checks on WooCommerce → Status.
	 */
	public static function render_report() {
		$upload_path = WCP_Plugin::get_upload_base_path();
		$upload_ok   = is_dir( $upload_path ) && wp_is_writable( $upload_path );
		$templates   = get_posts(
			array(
				'post_type'      => WCP_Template_CPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		$rows = array(
			array(
				'label' => __( 'GD extension', 'woo-personalization' ),
				'help'  => __( 'Required for compositing customer uploads onto mockups.', 'woo-personalization' ),
				'value' => extension_loaded( 'gd' ),
			),
			array(
				'label' => __( 'ZipArchive extension', 'woo-personalization' ),
				'help'  => __( 'Required for admin ZIP export of order design files.', 'woo-personalization' ),
				'value' => class_exists( 'ZipArchive' ),
			),
			array(
				'label' => __( 'Upload directory writable', 'woo-personalization' ),
				'help'  => $upload_path,
				'value' => $upload_ok,
			),
			array(
				'label' => __( 'Mockup templates', 'woo-personalization' ),
				'help'  => __( 'At least one published template is recommended before selling personalized products.', 'woo-personalization' ),
				'value' => ! empty( $templates ),
			),
			array(
				'label' => __( 'HPOS enabled', 'woo-personalization' ),
				'help'  => __( 'High-Performance Order Storage compatibility mode.', 'woo-personalization' ),
				'value' => class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(),
				'bool'  => true,
			),
		);

		?>
		<table class="wc_status_table widefat" cellspacing="0" id="wcp-status">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Woo Personalization">
						<h2><?php esc_html_e( 'Woo Personalization', 'woo-personalization' ); ?></h2>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td data-export-label="<?php echo esc_attr( $row['label'] ); ?>"><?php echo esc_html( $row['label'] ); ?></td>
						<td class="help"><?php echo esc_html( $row['help'] ); ?></td>
						<td>
							<?php
							if ( ! empty( $row['bool'] ) ) {
								echo $row['value']
									? '<mark class="yes"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Yes', 'woo-personalization' ) . '</mark>'
									: '<mark class="no">&ndash; ' . esc_html__( 'No', 'woo-personalization' ) . '</mark>';
							} else {
								echo $row['value']
									? '<mark class="yes"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Pass', 'woo-personalization' ) . '</mark>'
									: '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Fail', 'woo-personalization' ) . '</mark>';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

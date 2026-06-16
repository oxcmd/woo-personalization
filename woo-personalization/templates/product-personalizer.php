<?php
/**
 * Product page personalizer template.
 *
 * @var array<string, mixed> $config Frontend config.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wcp-personalizer" id="wcp-personalizer"
	data-print-x="<?php echo esc_attr( (string) $config['print_area']['x'] ); ?>"
	data-print-y="<?php echo esc_attr( (string) $config['print_area']['y'] ); ?>"
	data-print-width="<?php echo esc_attr( (string) $config['print_area']['width'] ); ?>"
	data-print-height="<?php echo esc_attr( (string) $config['print_area']['height'] ); ?>"
	data-default-fit="<?php echo esc_attr( $config['default_fit'] ); ?>">
	<h4><?php esc_html_e( 'Personalize your t-shirt', 'woo-personalization' ); ?></h4>

	<div class="wcp-preview-stage">
		<img src="<?php echo esc_url( $config['base_url'] ); ?>" alt="<?php esc_attr_e( 'T-shirt mockup', 'woo-personalization' ); ?>" class="wcp-base-image" />
		<div class="wcp-design-overlay" id="wcp-design-overlay">
			<img src="" alt="" class="wcp-design-image" id="wcp-design-image" />
		</div>
		<div class="wcp-print-area-guide" id="wcp-print-area-guide"></div>
	</div>

	<div class="wcp-controls">
		<label class="wcp-upload-label">
			<span><?php esc_html_e( 'Upload your design', 'woo-personalization' ); ?></span>
			<input type="file" id="wcp-file-input" accept="image/jpeg,image/png,image/webp" />
		</label>
		<button type="button" class="button wcp-remove-design" id="wcp-remove-design" hidden><?php esc_html_e( 'Remove design', 'woo-personalization' ); ?></button>
		<p class="wcp-status" id="wcp-status" aria-live="polite"></p>
	</div>

	<input type="hidden" name="wcp_upload_token" id="wcp_upload_token" value="" />
</div>

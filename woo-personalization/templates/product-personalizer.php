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
		<img src="<?php echo esc_url( $config['base_url'] ); ?>" alt="<?php esc_attr_e( 'T-shirt mockup', 'woo-personalization' ); ?>" class="wcp-base-image" id="wcp-base-image" data-plain-src="<?php echo esc_url( $config['base_url'] ); ?>" />
		<div class="wcp-design-overlay" id="wcp-design-overlay">
			<img src="" alt="" class="wcp-design-image" id="wcp-design-image" />
		</div>
		<div class="wcp-print-area-guide" id="wcp-print-area-guide"></div>
	</div>

	<div class="wcp-compare-toggle" id="wcp-compare-toggle" hidden>
		<span class="wcp-compare-label"><?php esc_html_e( 'Preview:', 'woo-personalization' ); ?></span>
		<button type="button" class="button wcp-compare-btn" data-mode="plain"><?php esc_html_e( 'Plain shirt', 'woo-personalization' ); ?></button>
		<button type="button" class="button wcp-compare-btn is-active" data-mode="design"><?php esc_html_e( 'Your design', 'woo-personalization' ); ?></button>
	</div>

	<div class="wcp-design-editor" id="wcp-design-editor" hidden>
		<p class="wcp-design-editor-help"><?php esc_html_e( 'Drag your design to reposition it. Use the slider to resize.', 'woo-personalization' ); ?></p>
		<label class="wcp-scale-label">
			<span><?php esc_html_e( 'Design size', 'woo-personalization' ); ?></span>
			<input type="range" id="wcp-design-scale" min="50" max="200" value="100" step="1" />
		</label>
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
	<input type="hidden" name="wcp_design_scale" id="wcp_design_scale" value="1" />
	<input type="hidden" name="wcp_design_offset_x" id="wcp_design_offset_x" value="0" />
	<input type="hidden" name="wcp_design_offset_y" id="wcp_design_offset_y" value="0" />
</div>

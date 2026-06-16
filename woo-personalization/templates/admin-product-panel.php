<?php
/**
 * WooCommerce product personalization panel.
 *
 * @var bool                $enabled
 * @var int                 $template_id
 * @var array<string,float> $override
 * @var array<int,string>   $templates
 * @var string|false        $preview
 * @var WP_Post             $post
 */

defined( 'ABSPATH' ) || exit;

$use_override = 'yes' === get_post_meta( $post->ID, '_wcp_use_print_override', true );
?>
<div id="wcp_personalization_panel" class="panel woocommerce_options_panel">
	<div class="options_group">
		<?php
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wcp_enabled',
				'label'       => __( 'Enable personalization', 'woo-personalization' ),
				'description' => __( 'Allow customers to upload a design on the product page.', 'woo-personalization' ),
				'value'       => $enabled ? 'yes' : 'no',
			)
		);
		?>

		<p class="form-field">
			<label for="_wcp_template_id"><?php esc_html_e( 'Mockup template', 'woo-personalization' ); ?> <span class="required">*</span></label>
			<select name="_wcp_template_id" id="_wcp_template_id" class="select short" required>
				<option value=""><?php esc_html_e( 'Select a template', 'woo-personalization' ); ?></option>
				<?php foreach ( $templates as $id => $title ) : ?>
					<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $template_id, $id ); ?>><?php echo esc_html( $title ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( empty( $templates ) ) : ?>
				<span class="description wcp-admin-warning"><?php esc_html_e( 'No mockup templates found. Create one under WooCommerce → Mockup Templates first.', 'woo-personalization' ); ?></span>
			<?php elseif ( $enabled && ! $template_id ) : ?>
				<span class="description wcp-admin-warning"><?php esc_html_e( 'Select a mockup template or the upload UI will not appear on the product page.', 'woo-personalization' ); ?></span>
			<?php endif; ?>
		</p>

		<p class="form-field">
			<label for="_wcp_use_print_override">
				<input type="checkbox" name="_wcp_use_print_override" id="_wcp_use_print_override" value="yes" <?php checked( $use_override ); ?> />
				<?php esc_html_e( 'Override print area for this product', 'woo-personalization' ); ?>
			</label>
			<span class="description"><?php esc_html_e( 'Use the visual selector below instead of typing X/Y/Width/Height manually.', 'woo-personalization' ); ?></span>
		</p>

		<div class="form-field wcp-override-fields wcp-product-override-editor" <?php echo ( $use_override && $preview ) ? '' : 'style="display:none;"'; ?>>
			<label><?php esc_html_e( 'Print area override', 'woo-personalization' ); ?></label>
			<p class="wcp-print-area-hint"><?php esc_html_e( 'Drag on the mockup to draw the print area. Drag the box to move it. Drag the corner dots to resize.', 'woo-personalization' ); ?></p>

			<div class="wcp-product-preview" id="wcp-product-preview">
				<img src="<?php echo esc_url( $preview ?: '' ); ?>" alt="" id="wcp-product-preview-image" />
				<?php
				$overlay_id = 'wcp-product-print-area-overlay';
				include WCP_PLUGIN_DIR . 'templates/partials/print-area-overlay.php';
				?>
			</div>

			<div class="wcp-print-area-values">
				<label><?php esc_html_e( 'X (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="0" max="100" name="_wcp_override_x" id="_wcp_override_x" value="<?php echo esc_attr( (string) $override['x'] ); ?>" class="short" /></label>
				<label><?php esc_html_e( 'Y (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="0" max="100" name="_wcp_override_y" id="_wcp_override_y" value="<?php echo esc_attr( (string) $override['y'] ); ?>" class="short" /></label>
				<label><?php esc_html_e( 'Width (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="1" max="100" name="_wcp_override_width" id="_wcp_override_width" value="<?php echo esc_attr( (string) $override['width'] ); ?>" class="short" /></label>
				<label><?php esc_html_e( 'Height (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="1" max="100" name="_wcp_override_height" id="_wcp_override_height" value="<?php echo esc_attr( (string) $override['height'] ); ?>" class="short" /></label>
			</div>
			<span class="wcp-override-summary" id="wcp-override-summary"></span>
		</div>
	</div>
</div>

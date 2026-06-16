<div class="wcp-template-meta">
	<p>
		<label for="wcp_base_image_id"><strong><?php esc_html_e( 'Base mockup image', 'woo-personalization' ); ?></strong></label>
	</p>
	<p>
		<input type="hidden" id="wcp_base_image_id" name="wcp_base_image_id" value="<?php echo esc_attr( (string) $base_image_id ); ?>" />
		<button type="button" class="button" id="wcp-select-base-image"><?php esc_html_e( 'Select image', 'woo-personalization' ); ?></button>
		<button type="button" class="button" id="wcp-remove-base-image" <?php echo $image_url ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'woo-personalization' ); ?></button>
	</p>

	<div class="wcp-template-preview-wrap" <?php echo $image_url ? '' : 'style="display:none"'; ?>>
		<p class="wcp-print-area-hint"><?php esc_html_e( 'Drag on the image to draw the print area. Drag the box to move it. Drag the corner dots to resize.', 'woo-personalization' ); ?></p>
		<div class="wcp-template-preview" id="wcp-template-preview">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="" id="wcp-base-image-preview" />
			<?php
			$overlay_id = 'wcp-print-area-overlay';
			include WCP_PLUGIN_DIR . 'templates/partials/print-area-overlay.php';
			?>
		</div>

		<div class="wcp-print-area-values">
			<label><?php esc_html_e( 'X (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="0" max="100" name="wcp_print_x" id="wcp_print_x" value="<?php echo esc_attr( (string) $print_area['x'] ); ?>" class="small-text wcp-print-input" /></label>
			<label><?php esc_html_e( 'Y (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="0" max="100" name="wcp_print_y" id="wcp_print_y" value="<?php echo esc_attr( (string) $print_area['y'] ); ?>" class="small-text wcp-print-input" /></label>
			<label><?php esc_html_e( 'Width (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="1" max="100" name="wcp_print_width" id="wcp_print_width" value="<?php echo esc_attr( (string) $print_area['width'] ); ?>" class="small-text wcp-print-input" /></label>
			<label><?php esc_html_e( 'Height (%)', 'woo-personalization' ); ?> <input type="number" step="0.1" min="1" max="100" name="wcp_print_height" id="wcp_print_height" value="<?php echo esc_attr( (string) $print_area['height'] ); ?>" class="small-text wcp-print-input" /></label>
		</div>
	</div>

	<table class="form-table">
		<tr>
			<th scope="row"><label for="wcp_side"><?php esc_html_e( 'Side', 'woo-personalization' ); ?></label></th>
			<td>
				<select name="wcp_side" id="wcp_side">
					<option value="front" <?php selected( $side, 'front' ); ?>><?php esc_html_e( 'Front', 'woo-personalization' ); ?></option>
					<option value="back" <?php selected( $side, 'back' ); ?>><?php esc_html_e( 'Back', 'woo-personalization' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="wcp_default_fit"><?php esc_html_e( 'Image fit', 'woo-personalization' ); ?></label></th>
			<td>
				<select name="wcp_default_fit" id="wcp_default_fit">
					<option value="cover" <?php selected( $default_fit, 'cover' ); ?>><?php esc_html_e( 'Cover', 'woo-personalization' ); ?></option>
					<option value="contain" <?php selected( $default_fit, 'contain' ); ?>><?php esc_html_e( 'Contain', 'woo-personalization' ); ?></option>
				</select>
			</td>
		</tr>
	</table>
</div>

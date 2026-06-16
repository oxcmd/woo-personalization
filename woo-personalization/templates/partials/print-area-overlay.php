<?php
/**
 * Shared overlay markup for interactive print area selection.
 *
 * @package WooPersonalization
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="wcp-print-area-overlay"
	id="<?php echo esc_attr( $overlay_id ); ?>"
	data-label="<?php esc_attr_e( 'Print area', 'woo-personalization' ); ?>"
>
	<span class="wcp-print-area-handle wcp-handle-nw" data-handle="nw" aria-hidden="true"></span>
	<span class="wcp-print-area-handle wcp-handle-ne" data-handle="ne" aria-hidden="true"></span>
	<span class="wcp-print-area-handle wcp-handle-sw" data-handle="sw" aria-hidden="true"></span>
	<span class="wcp-print-area-handle wcp-handle-se" data-handle="se" aria-hidden="true"></span>
</div>

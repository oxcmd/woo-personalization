(function ($) {
	'use strict';

	var editor = null;

	function initEditor() {
		if (!$('#wcp-template-preview').length) {
			return;
		}

		if (editor) {
			editor.destroy();
		}

		editor = new window.WCPPrintAreaEditor({
			container: '#wcp-template-preview',
			inputs: {
				x: '#wcp_print_x',
				y: '#wcp_print_y',
				width: '#wcp_print_width',
				height: '#wcp_print_height'
			}
		});
	}

	function bindMediaPicker() {
		var frame;

		$('#wcp-select-base-image').on('click', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Select mockup image',
				button: { text: 'Use image' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#wcp_base_image_id').val(attachment.id);
				$('#wcp-base-image-preview').attr('src', attachment.url);
				$('.wcp-template-preview-wrap').show();
				$('#wcp-remove-base-image').show();
				initEditor();
			});

			frame.open();
		});

		$('#wcp-remove-base-image').on('click', function (event) {
			event.preventDefault();
			$('#wcp_base_image_id').val('');
			$('#wcp-base-image-preview').attr('src', '');
			$('.wcp-template-preview-wrap').hide();
			$(this).hide();
		});
	}

	$(function () {
		bindMediaPicker();
		initEditor();
	});
}(jQuery));

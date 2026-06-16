(function ($) {
	'use strict';

	var config = window.wcpProductPanel || {};
	var editor = null;

	function getTemplateId() {
		return parseInt($('#_wcp_template_id').val(), 10) || 0;
	}

	function isOverrideEnabled() {
		return $('#_wcp_use_print_override').is(':checked');
	}

	function setInputValues(area) {
		$('#_wcp_override_x').val(area.x);
		$('#_wcp_override_y').val(area.y);
		$('#_wcp_override_width').val(area.width);
		$('#_wcp_override_height').val(area.height);
	}

	function updateSummary() {
		var area = {
			x: $('#_wcp_override_x').val(),
			y: $('#_wcp_override_y').val(),
			width: $('#_wcp_override_width').val(),
			height: $('#_wcp_override_height').val()
		};
		$('#wcp-override-summary').text(
			'X ' + area.x + '% · Y ' + area.y + '% · W ' + area.width + '% · H ' + area.height + '%'
		);
	}

	function destroyEditor() {
		if (editor) {
			editor.destroy();
			editor = null;
		}
	}

	function initEditor() {
		destroyEditor();

		var templateId = getTemplateId();
		var template = config.templates && config.templates[templateId];

		if (!isOverrideEnabled() || !template || !template.url) {
			$('.wcp-product-override-editor').hide();
			return;
		}

		$('.wcp-product-override-editor').show();
		$('#wcp-product-preview-image').attr('src', template.url);

		editor = new window.WCPPrintAreaEditor({
			container: '#wcp-product-preview',
			inputs: {
				x: '#_wcp_override_x',
				y: '#_wcp_override_y',
				width: '#_wcp_override_width',
				height: '#_wcp_override_height'
			}
		});

		$('#_wcp_override_x, #_wcp_override_y, #_wcp_override_width, #_wcp_override_height')
			.off('input.wcpSummary')
			.on('input.wcpSummary', updateSummary);

		updateSummary();
	}

	function syncOverrideFromTemplate() {
		var templateId = getTemplateId();
		var template = config.templates && config.templates[templateId];
		if (template && template.print_area) {
			setInputValues(template.print_area);
		}
	}

	$(function () {
		$('#_wcp_template_id').on('change', function () {
			if (isOverrideEnabled()) {
				syncOverrideFromTemplate();
			}
			initEditor();
		});

		$('#_wcp_use_print_override').on('change', function () {
			if (this.checked) {
				syncOverrideFromTemplate();
			}
			initEditor();
		});

		initEditor();
	});
}(jQuery));

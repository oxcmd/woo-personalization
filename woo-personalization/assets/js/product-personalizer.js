(function ($) {
	'use strict';

	var config = window.wcpPersonalizer || {};
	var $personalizer = $('#wcp-personalizer');
	var $fileInput = $('#wcp-file-input');
	var $tokenInput = $('#wcp_upload_token');
	var $status = $('#wcp-status');
	var $designImage = $('#wcp-design-image');
	var $designOverlay = $('#wcp-design-overlay');
	var $printGuide = $('#wcp-print-area-guide');
	var $removeBtn = $('#wcp-remove-design');
	var $form = $('form.cart');
	var $baseImage = $('#wcp-base-image');
	var $compareToggle = $('#wcp-compare-toggle');
	var plainMockupUrl = $baseImage.data('plain-src') || '';
	var designedMockupUrl = '';
	var previewMode = 'design';

	function getPrintArea() {
		return {
			x: parseFloat($personalizer.data('print-x')) || 25,
			y: parseFloat($personalizer.data('print-y')) || 28,
			width: parseFloat($personalizer.data('print-width')) || 50,
			height: parseFloat($personalizer.data('print-height')) || 45
		};
	}

	function applyLayout() {
		var area = getPrintArea();
		var fit = $personalizer.data('default-fit') || 'cover';

		$designOverlay
			.css({
				left: area.x + '%',
				top: area.y + '%',
				width: area.width + '%',
				height: area.height + '%'
			})
			.toggleClass('fit-contain', fit === 'contain')
			.toggleClass('fit-cover', fit !== 'contain');

		$printGuide.css({
			left: area.x + '%',
			top: area.y + '%',
			width: area.width + '%',
			height: area.height + '%'
		});
	}

	function setStatus(message, type) {
		$status.text(message || '').removeClass('is-error is-success is-warning');
		if (type) {
			$status.addClass('is-' + type);
		}
	}

	function resetDesign() {
		$tokenInput.val('');
		$fileInput.val('');
		$designImage.attr('src', '').removeClass('is-visible');
		$removeBtn.prop('hidden', true);
		$printGuide.show();
		designedMockupUrl = '';
		$compareToggle.prop('hidden', true);
		$compareToggle.find('.wcp-compare-btn').removeClass('is-active');
		$compareToggle.find('[data-mode="design"]').addClass('is-active');
		previewMode = 'design';
		if (plainMockupUrl) {
			$baseImage.attr('src', plainMockupUrl);
		}
		setStatus('');
	}

	function setPreviewMode(mode) {
		if (!designedMockupUrl) {
			return;
		}

		previewMode = mode === 'plain' ? 'plain' : 'design';
		$compareToggle.find('.wcp-compare-btn').removeClass('is-active');
		$compareToggle.find('[data-mode="' + previewMode + '"]').addClass('is-active');
		$baseImage.attr('src', previewMode === 'plain' ? plainMockupUrl : designedMockupUrl);
	}

	function showLocalPreview(file) {
		var reader = new FileReader();
		reader.onload = function (event) {
			$designImage.attr('src', event.target.result).addClass('is-visible');
			$removeBtn.prop('hidden', false);
		};
		reader.readAsDataURL(file);
	}

	function uploadFile(file) {
		var formData = new FormData();
		formData.append('action', 'wcp_upload_image');
		formData.append('nonce', config.nonce);
		formData.append('product_id', config.productId);
		formData.append('wcp_image', file);

		setStatus(config.i18n.uploading, '');

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		})
			.done(function (response) {
				if (!response || !response.success) {
					setStatus((response && response.data && response.data.message) || config.i18n.uploadError, 'error');
					resetDesign();
					return;
				}

				$tokenInput.val(response.data.token);
				if (response.data.preview_url) {
					designedMockupUrl = response.data.preview_url;
					setPreviewMode('design');
					$compareToggle.prop('hidden', false);
					$designImage.attr('src', '').removeClass('is-visible');
					$printGuide.hide();
				}
				if (response.data.dpi_warning) {
					setStatus(response.data.dpi_warning, 'warning');
				} else {
					setStatus(config.i18n.uploadSuccess, 'success');
				}
			})
			.fail(function () {
				setStatus(config.i18n.uploadError, 'error');
				resetDesign();
			});
	}

	$fileInput.on('change', function () {
		var file = this.files && this.files[0];
		if (!file) {
			return;
		}

		if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) {
			setStatus(config.i18n.invalidType, 'error');
			resetDesign();
			return;
		}

		showLocalPreview(file);
		uploadFile(file);
	});

	$removeBtn.on('click', function () {
		resetDesign();
	});

	$compareToggle.on('click', '.wcp-compare-btn', function () {
		setPreviewMode($(this).data('mode'));
	});

	if ($form.length) {
		$form.on('submit', function (event) {
			if (!$personalizer.length) {
				return;
			}

			if (!$tokenInput.val()) {
				event.preventDefault();
				setStatus(config.i18n.required, 'error');
			}
		});
	}

	applyLayout();
}(jQuery));

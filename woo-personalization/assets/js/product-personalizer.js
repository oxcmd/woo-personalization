(function ($) {
	'use strict';

	var config = window.wcpPersonalizer || {};
	var $personalizer = $('#wcp-personalizer');
	var $fileInput = $('#wcp-file-input');
	var $tokenInput = $('#wcp_upload_token');
	var $scaleInput = $('#wcp_design_scale');
	var $offsetXInput = $('#wcp_design_offset_x');
	var $offsetYInput = $('#wcp_design_offset_y');
	var $scaleSlider = $('#wcp-design-scale');
	var $status = $('#wcp-status');
	var $designImage = $('#wcp-design-image');
	var $designOverlay = $('#wcp-design-overlay');
	var $designEditor = $('#wcp-design-editor');
	var $printGuide = $('#wcp-print-area-guide');
	var $removeBtn = $('#wcp-remove-design');
	var $form = $('form.cart');
	var $baseImage = $('#wcp-base-image');
	var $compareToggle = $('#wcp-compare-toggle');
	var plainMockupUrl = $baseImage.data('plain-src') || '';
	var designedMockupUrl = '';
	var previewMode = 'design';
	var designTransform = { scale: 1, offset_x: 0, offset_y: 0 };
	var updateTimer = null;
	var dragging = false;
	var dragStartX = 0;
	var dragStartY = 0;
	var dragStartOffsetX = 0;
	var dragStartOffsetY = 0;

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

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

	function syncHiddenTransform() {
		$scaleInput.val(String(designTransform.scale));
		$offsetXInput.val(String(designTransform.offset_x));
		$offsetYInput.val(String(designTransform.offset_y));
	}

	function applyDesignTransform() {
		$designImage.css(
			'transform',
			'translate(' + designTransform.offset_x + '%, ' + designTransform.offset_y + '%) scale(' + designTransform.scale + ')'
		);
		syncHiddenTransform();
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
		$designImage.attr('src', '').removeClass('is-visible').css('transform', '');
		$removeBtn.prop('hidden', true);
		$designEditor.prop('hidden', true);
		$printGuide.show();
		$designOverlay.removeClass('is-editing');
		designedMockupUrl = '';
		designTransform = { scale: 1, offset_x: 0, offset_y: 0 };
		$scaleSlider.val(100);
		syncHiddenTransform();
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
			applyDesignTransform();
		};
		reader.readAsDataURL(file);
	}

	function scheduleDesignUpdate() {
		if (!$tokenInput.val()) {
			return;
		}

		window.clearTimeout(updateTimer);
		updateTimer = window.setTimeout(updateDesignPreview, 350);
	}

	function updateDesignPreview() {
		if (!$tokenInput.val()) {
			return;
		}

		$.post(config.ajaxUrl, {
			action: 'wcp_update_design',
			nonce: config.nonce,
			product_id: config.productId,
			token: $tokenInput.val(),
			scale: designTransform.scale,
			offset_x: designTransform.offset_x,
			offset_y: designTransform.offset_y
		})
			.done(function (response) {
				if (!response || !response.success || !response.data.preview_url) {
					return;
				}

				designedMockupUrl = response.data.preview_url;
				if (previewMode === 'design') {
					$baseImage.attr('src', designedMockupUrl);
				}
			});
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
				$designEditor.prop('hidden', false);
				$designOverlay.addClass('is-editing');
				$printGuide.hide();

				if (response.data.preview_url) {
					designedMockupUrl = response.data.preview_url;
					setPreviewMode('design');
					$compareToggle.prop('hidden', false);
				}

				if (response.data.dpi_warning) {
					setStatus(response.data.dpi_warning, 'warning');
				} else {
					setStatus(config.i18n.positionHint, 'success');
				}
			})
			.fail(function () {
				setStatus(config.i18n.uploadError, 'error');
				resetDesign();
			});
	}

	function pointerPosition(event) {
		if (event.originalEvent && event.originalEvent.touches && event.originalEvent.touches[0]) {
			return {
				x: event.originalEvent.touches[0].clientX,
				y: event.originalEvent.touches[0].clientY
			};
		}

		return {
			x: event.clientX,
			y: event.clientY
		};
	}

	$designOverlay.on('mousedown touchstart', function (event) {
		if (!$designImage.hasClass('is-visible') || !$tokenInput.val()) {
			return;
		}

		dragging = true;
		$designOverlay.addClass('is-dragging-design');
		var point = pointerPosition(event);
		dragStartX = point.x;
		dragStartY = point.y;
		dragStartOffsetX = designTransform.offset_x;
		dragStartOffsetY = designTransform.offset_y;
		event.preventDefault();
	});

	$(document).on('mousemove touchmove', function (event) {
		if (!dragging) {
			return;
		}

		var overlay = $designOverlay[0].getBoundingClientRect();
		if (!overlay.width || !overlay.height) {
			return;
		}

		var point = pointerPosition(event);
		var deltaX = ((point.x - dragStartX) / overlay.width) * 100;
		var deltaY = ((point.y - dragStartY) / overlay.height) * 100;

		designTransform.offset_x = clamp(dragStartOffsetX + deltaX, -50, 50);
		designTransform.offset_y = clamp(dragStartOffsetY + deltaY, -50, 50);
		applyDesignTransform();
		scheduleDesignUpdate();
		event.preventDefault();
	});

	$(document).on('mouseup touchend touchcancel', function () {
		if (!dragging) {
			return;
		}

		dragging = false;
		$designOverlay.removeClass('is-dragging-design');
		scheduleDesignUpdate();
	});

	$scaleSlider.on('input change', function () {
		designTransform.scale = clamp(parseInt(this.value, 10) / 100, 0.5, 2);
		applyDesignTransform();
		scheduleDesignUpdate();
	});

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

		designTransform = { scale: 1, offset_x: 0, offset_y: 0 };
		$scaleSlider.val(100);
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

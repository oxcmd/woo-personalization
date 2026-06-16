(function (window, $) {
	'use strict';

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function round(value) {
		return Math.round(value * 10) / 10;
	}

	/**
	 * Interactive print-area selector (draw, move, resize).
	 *
	 * @param {Object} options Editor options.
	 */
	function PrintAreaEditor(options) {
		this.$container = $(options.container);
		this.$overlay = this.$container.find(options.overlaySelector || '.wcp-print-area-overlay');
		this.$inputs = {
			x: $(options.inputs.x),
			y: $(options.inputs.y),
			width: $(options.inputs.width),
			height: $(options.inputs.height)
		};
		this.minSize = options.minSize || 5;
		this.enabled = options.enabled !== false;
		this.dragState = null;

		this.bind();
		this.render();
	}

	PrintAreaEditor.prototype.getBounds = function () {
		var rect = this.$container[0].getBoundingClientRect();
		return {
			width: rect.width || 1,
			height: rect.height || 1
		};
	};

	PrintAreaEditor.prototype.readArea = function () {
		return {
			x: parseFloat(this.$inputs.x.val()) || 0,
			y: parseFloat(this.$inputs.y.val()) || 0,
			width: parseFloat(this.$inputs.width.val()) || this.minSize,
			height: parseFloat(this.$inputs.height.val()) || this.minSize
		};
	};

	PrintAreaEditor.prototype.writeArea = function (area) {
		area = {
			x: round(clamp(area.x, 0, 100)),
			y: round(clamp(area.y, 0, 100)),
			width: round(clamp(area.width, this.minSize, 100)),
			height: round(clamp(area.height, this.minSize, 100))
		};

		if (area.x + area.width > 100) {
			area.width = round(100 - area.x);
		}
		if (area.y + area.height > 100) {
			area.height = round(100 - area.y);
		}

		this.$inputs.x.val(area.x);
		this.$inputs.y.val(area.y);
		this.$inputs.width.val(area.width);
		this.$inputs.height.val(area.height);
		this.render();
	};

	PrintAreaEditor.prototype.render = function () {
		var area = this.readArea();
		this.$overlay.css({
			left: area.x + '%',
			top: area.y + '%',
			width: area.width + '%',
			height: area.height + '%'
		});
	};

	PrintAreaEditor.prototype.setEnabled = function (enabled) {
		this.enabled = !!enabled;
		this.$container.toggleClass('wcp-editor-disabled', !this.enabled);
	};

	PrintAreaEditor.prototype.eventToPercent = function (event) {
		var bounds = this.getBounds();
		var offset = this.$container.offset();
		var x = ((event.pageX - offset.left) / bounds.width) * 100;
		var y = ((event.pageY - offset.top) / bounds.height) * 100;
		return {
			x: clamp(x, 0, 100),
			y: clamp(y, 0, 100)
		};
	};

	PrintAreaEditor.prototype.onPointerMove = function (event) {
		if (!this.dragState) {
			return;
		}

		event.preventDefault();
		var point = this.eventToPercent(event);
		var state = this.dragState;
		var area;

		if (state.mode === 'draw') {
			area = {
				x: Math.min(state.start.x, point.x),
				y: Math.min(state.start.y, point.y),
				width: Math.abs(point.x - state.start.x),
				height: Math.abs(point.y - state.start.y)
			};
		} else if (state.mode === 'move') {
			area = {
				x: state.origin.x + (point.x - state.start.x),
				y: state.origin.y + (point.y - state.start.y),
				width: state.origin.width,
				height: state.origin.height
			};
			if (area.x + area.width > 100) {
				area.x = 100 - area.width;
			}
			if (area.y + area.height > 100) {
				area.y = 100 - area.height;
			}
		} else if (state.mode === 'resize') {
			area = $.extend({}, state.origin);
			var handle = state.handle;

			if (handle.indexOf('e') !== -1) {
				area.width = Math.max(this.minSize, point.x - area.x);
			}
			if (handle.indexOf('s') !== -1) {
				area.height = Math.max(this.minSize, point.y - area.y);
			}
			if (handle.indexOf('w') !== -1) {
				var right = state.origin.x + state.origin.width;
				area.x = Math.min(point.x, right - this.minSize);
				area.width = right - area.x;
			}
			if (handle.indexOf('n') !== -1) {
				var bottom = state.origin.y + state.origin.height;
				area.y = Math.min(point.y, bottom - this.minSize);
				area.height = bottom - area.y;
			}
		}

		this.writeArea(area);
	};

	PrintAreaEditor.prototype.onPointerUp = function () {
		if (!this.dragState) {
			return;
		}
		this.dragState = null;
		this.$container.removeClass('wcp-is-dragging');
		$(document).off('.wcpPrintAreaEditor');
	};

	PrintAreaEditor.prototype.startDrag = function (mode, event, extra) {
		if (!this.enabled) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		this.dragState = $.extend(
			{
				mode: mode,
				start: this.eventToPercent(event),
				origin: this.readArea()
			},
			extra || {}
		);

		this.$container.addClass('wcp-is-dragging');
		$(document)
			.on('mousemove.wcpPrintAreaEditor', this.onPointerMove.bind(this))
			.on('mouseup.wcpPrintAreaEditor', this.onPointerUp.bind(this));
	};

	PrintAreaEditor.prototype.bind = function () {
		var self = this;

		this.$container.on('mousedown', '.wcp-print-area-handle', function (event) {
			self.startDrag('resize', event, { handle: $(this).data('handle') });
		});

		this.$overlay.on('mousedown', function (event) {
			if ($(event.target).hasClass('wcp-print-area-handle')) {
				return;
			}
			self.startDrag('move', event);
		});

		this.$container.on('mousedown', function (event) {
			if (!self.enabled) {
				return;
			}
			if ($(event.target).closest('.wcp-print-area-overlay').length) {
				return;
			}
			if (!$(event.target).is('img')) {
				return;
			}
			self.startDrag('draw', event);
		});

		$.each(this.$inputs, function (_, $input) {
			$input.on('input change', function () {
				self.render();
			});
		});

		$(window).on('resize.wcpPrintAreaEditor', function () {
			self.render();
		});
	};

	PrintAreaEditor.prototype.destroy = function () {
		$(window).off('.wcpPrintAreaEditor');
		$(document).off('.wcpPrintAreaEditor');
		this.$container.off();
		this.$overlay.off();
	};

	window.WCPPrintAreaEditor = PrintAreaEditor;
}(window, jQuery));

// FireVPS theme enhancements – tab overflow handling
(function () {
	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return;
	}

	var ROOT_SELECTOR = '.pwpl-table--theme-firevps';
	var navRegistry = [];
	var ctaPlans = [];

	function updateOverflowState(nav) {
		if (!nav || !nav.parentNode) {
			return;
		}
		var tablist = nav.querySelector('[data-fvps-tablist]');
		if (!tablist) {
			return;
		}
		var navWidth = nav.clientWidth;
		var tabsWidth = tablist.scrollWidth;
		var isOverflowing = tabsWidth > navWidth + 1;
		nav.classList.toggle('is-overflowing', isOverflowing);
		if (nav._fvpsRailCtx && typeof nav._fvpsRailCtx.requestUpdate === 'function') {
			nav._fvpsRailCtx.requestUpdate();
		} else {
			nav.classList.toggle('is-scrollable', isOverflowing);
		}
	}

	function setupResizeObserver(nav) {
		var tablist = nav.querySelector('[data-fvps-tablist]');
		if (!tablist) {
			return;
		}

		if ('ResizeObserver' in window) {
			var observer = new ResizeObserver(function () {
				updateOverflowState(nav);
			});
			observer.observe(nav);
			observer.observe(tablist);
			nav._fvpsResizeObserver = observer;
		} else {
			navRegistry.push(nav);
		}

		updateOverflowState(nav);
		ensureScrollRail(nav, nav, { railClass: 'fvps-tabs-rail', minThumb: 32 });
	}

	function initNavs() {
		var navs = document.querySelectorAll(ROOT_SELECTOR + ' .fvps-dimension-nav');
		if (!navs.length) {
			return;
		}
		navs.forEach(function (nav) {
			if (nav._fvpsTabsInitialized) {
				return;
			}
			nav._fvpsTabsInitialized = true;
			setupResizeObserver(nav);
			ensureScrollRail(nav, nav, { railClass: 'fvps-tabs-rail', minThumb: 32 });
		});
	}

	function initPlanRails() {
		var wrappers = document.querySelectorAll(ROOT_SELECTOR + ' .fvps-plan-rail-wrapper');
		if (!wrappers.length) {
			return;
		}
		wrappers.forEach(function (wrapper) {
			var scroller = wrapper.querySelector('.pwpl-plan-grid');
			if (!scroller) {
				return;
			}
			ensureScrollRail(wrapper, scroller, {
				railClass: 'fvps-plans-rail',
				minThumb: 48,
				snap: true,
				snapDelay: 180,
				itemsProvider: function (node) {
					return Array.from(node.querySelectorAll('.pwpl-plan')).filter(function (item) {
						return item && item.offsetParent !== null && !item.hidden && !item.classList.contains('pwpl-hidden');
					});
				}
			});
		});
	}

function ensureScrollRail(container, scroller, options) {
	scroller = scroller || container;
	options = Object.assign({
		railClass: 'fvps-tabs-rail',
		minThumb: 32,
		snap: false,
		snapDelay: 180,
		itemSelector: null,
		itemsProvider: null,
		animationDuration: 320
	}, options || {});

	if (container._fvpsRailCtx && typeof container._fvpsRailCtx.cleanup === 'function') {
		container._fvpsRailCtx.cleanup();
	}

	var external = scroller._pwplScrollEnhancer = scroller._pwplScrollEnhancer || {};

	var rail = container.querySelector('.' + options.railClass);
	if (!rail) {
		rail = document.createElement('div');
		rail.className = options.railClass;
		rail.setAttribute('aria-hidden', 'true');
		var trackEl = document.createElement('div');
		trackEl.className = options.railClass + '__track';
		var thumbEl = document.createElement('div');
		thumbEl.className = options.railClass + '__thumb';
		trackEl.appendChild(thumbEl);
		rail.appendChild(trackEl);
		container.appendChild(rail);
	}
	var trackNode = rail.querySelector('.' + options.railClass + '__track');
	var thumbNode = rail.querySelector('.' + options.railClass + '__thumb');

	var ctx = container._fvpsRailCtx = {
		container: container,
		scroller: scroller,
		options: options,
		external: external,
		rail: rail,
		track: trackNode,
		thumb: thumbNode,
		isDragging: false,
		isProgrammatic: false,
		hideTimer: null,
		snapTimer: null,
		rafPending: false,
		maxScroll: 0,
		maxThumbLeft: 0,
		thumbWidth: 0,
		lastThumbLeft: 0,
		cleanupCallbacks: []
	};

	var reduceMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
	ctx.prefersReducedMotion = reduceMotionQuery ? reduceMotionQuery.matches : false;
	if (reduceMotionQuery) {
		var handleReduceMotion = function () {
			ctx.prefersReducedMotion = reduceMotionQuery.matches;
		};
		if (typeof reduceMotionQuery.addEventListener === 'function') {
			reduceMotionQuery.addEventListener('change', handleReduceMotion);
			ctx.cleanupCallbacks.push(function () {
				reduceMotionQuery.removeEventListener('change', handleReduceMotion);
			});
		} else if (typeof reduceMotionQuery.addListener === 'function') {
			reduceMotionQuery.addListener(handleReduceMotion);
			ctx.cleanupCallbacks.push(function () {
				reduceMotionQuery.removeListener(handleReduceMotion);
			});
		}
	}

	function cleanup() {
		ctx.cleanupCallbacks.forEach(function (fn) {
			try {
				fn();
			} catch (err) {}
		});
		ctx.cleanupCallbacks = [];
		if (ctx.snapTimer) {
			clearTimeout(ctx.snapTimer);
			ctx.snapTimer = null;
		}
		if (ctx.hideTimer) {
			clearTimeout(ctx.hideTimer);
			ctx.hideTimer = null;
		}
		if (ctx.external) {
			if (ctx.external.scrollTo === ctx.scrollTo) {
				delete ctx.external.scrollTo;
			}
			if (ctx.external.syncRail === ctx.requestUpdate) {
				delete ctx.external.syncRail;
			}
			if (ctx.external.requestUpdate === ctx.requestUpdate) {
				delete ctx.external.requestUpdate;
			}
			if (ctx.external.scheduleSnap === ctx.scheduleSnap) {
				delete ctx.external.scheduleSnap;
			}
		}
	}
	ctx.cleanup = cleanup;

	function getItems() {
		if (typeof ctx.options.itemsProvider === 'function') {
			return ctx.options.itemsProvider(scroller) || [];
		}
		var selector = ctx.options.itemSelector || ':scope > *';
		return Array.from(scroller.querySelectorAll(selector)).filter(function (item) {
			return item && item.offsetParent !== null && !item.hidden && !item.classList.contains('pwpl-hidden');
		});
	}

	function updateThumb() {
		if (!container.classList.contains('is-scrollable')) {
			return;
		}
		var scrollLeft = scroller.scrollLeft;
		var thumbLeft = ctx.maxScroll ? (scrollLeft / ctx.maxScroll) * ctx.maxThumbLeft : 0;
		thumbLeft = Math.max(0, Math.min(ctx.maxThumbLeft, thumbLeft));
		ctx.lastThumbLeft = thumbLeft;
		ctx.thumb.style.width = ctx.thumbWidth + 'px';
		ctx.thumb.style.transform = 'translateX(' + thumbLeft + 'px)';
	}

	function updateMetrics() {
		var scrollWidth = scroller.scrollWidth;
		var clientWidth = scroller.clientWidth;
		ctx.maxScroll = Math.max(scrollWidth - clientWidth, 0);
		var isScrollable = ctx.maxScroll > 1;
		container.classList.toggle('is-scrollable', isScrollable);
		container.classList.toggle('is-overflowing', isScrollable);
		ctx.rail.hidden = !isScrollable;
		if (!isScrollable) {
			ctx.thumb.style.transform = 'translateX(0)';
			container.classList.remove('is-user-scrolling');
			container.classList.remove('is-dragging');
			cancelSnap();
			return;
		}
		var trackWidth = ctx.track.clientWidth;
		if (!trackWidth) {
			requestUpdate();
			return;
		}
		var ratio = clientWidth / scrollWidth;
		var minThumb = ctx.options.minThumb || 32;
		ctx.thumbWidth = Math.min(trackWidth, Math.max(minThumb, trackWidth * ratio));
		ctx.maxThumbLeft = Math.max(trackWidth - ctx.thumbWidth, 0);
		updateThumb();
	}

	function requestUpdate() {
		if (ctx.rafPending) {
			return;
		}
		ctx.rafPending = true;
		window.requestAnimationFrame(function () {
			ctx.rafPending = false;
			updateMetrics();
		});
	}

	function cancelSnap() {
		if (ctx.snapTimer) {
			clearTimeout(ctx.snapTimer);
			ctx.snapTimer = null;
		}
	}

	function snapToNearest(forceInstant) {
		if (!ctx.options.snap) {
			return;
		}
		var items = getItems();
		if (!items.length || ctx.maxScroll <= 0) {
			return;
		}
		var current = scroller.scrollLeft;
		var target = current;
		var bestDelta = Infinity;
		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			var left = item.offsetLeft;
			var delta = Math.abs(current - left);
			if (delta < bestDelta) {
				bestDelta = delta;
				target = left;
			}
		}
		target = Math.max(0, Math.min(ctx.maxScroll, target));
		if (Math.abs(target - current) < 1) {
			return;
		}
		scrollTo(target, { behavior: forceInstant ? 'auto' : 'smooth' });
	}

	function scheduleSnap() {
		if (!ctx.options.snap) {
			return;
		}
		if (ctx.isDragging || ctx.isProgrammatic) {
			return;
		}
		cancelSnap();
		ctx.snapTimer = setTimeout(function () {
			ctx.snapTimer = null;
			snapToNearest();
		}, ctx.options.snapDelay || 180);
	}

	function scheduleHide() {
		container.classList.add('is-user-scrolling');
		if (ctx.hideTimer) {
			clearTimeout(ctx.hideTimer);
		}
		ctx.hideTimer = setTimeout(function () {
			container.classList.remove('is-user-scrolling');
			ctx.hideTimer = null;
		}, 600);
	}

	function scrollTo(target, opts) {
		opts = opts || {};
		target = Math.max(0, Math.min(ctx.maxScroll, target));
		if (ctx.maxScroll <= 0) {
			scroller.scrollLeft = target;
			ctx.isProgrammatic = false;
			requestUpdate();
			return;
		}
		cancelSnap();
		ctx.isProgrammatic = true;
		scheduleHide();
		var behavior = opts.behavior || 'smooth';
		if (ctx.prefersReducedMotion) {
			behavior = 'auto';
		}
		var usedNative = false;
		if (typeof scroller.scrollTo === 'function') {
			try {
				scroller.scrollTo({ left: target, behavior: behavior });
				usedNative = true;
			} catch (err) {
				usedNative = false;
			}
		}
		if (!usedNative) {
			var start = scroller.scrollLeft;
			var distance = target - start;
			if (Math.abs(distance) < 1) {
				scroller.scrollLeft = target;
				ctx.isProgrammatic = false;
				requestUpdate();
				scheduleSnap();
				return;
			}
			var duration = ctx.prefersReducedMotion ? 0 : (opts.duration || ctx.options.animationDuration || 320);
			var startTime = null;
			var easeOut = function (t) { return t * (2 - t); };
			window.requestAnimationFrame(function step(ts) {
				if (!startTime) {
					startTime = ts;
				}
				var progress = duration ? Math.min((ts - startTime) / duration, 1) : 1;
				var eased = duration ? easeOut(progress) : 1;
				scroller.scrollLeft = start + distance * eased;
				if (progress < 1) {
					window.requestAnimationFrame(step);
				} else {
					ctx.isProgrammatic = false;
					requestUpdate();
					scheduleSnap();
				}
			});
			return;
		}
		if (behavior === 'auto') {
			ctx.isProgrammatic = false;
			requestUpdate();
			scheduleSnap();
			return;
		}
		var durationEstimate = opts.duration || ctx.options.animationDuration || 320;
		window.setTimeout(function () {
			ctx.isProgrammatic = false;
			requestUpdate();
			scheduleSnap();
		}, durationEstimate + (ctx.options.snapDelay || 180));
	}

	ctx.updateThumb = updateThumb;
	ctx.updateMetrics = updateMetrics;
	ctx.requestUpdate = requestUpdate;
	ctx.scrollTo = scrollTo;
	ctx.scheduleSnap = scheduleSnap;
	ctx.snapToNearest = snapToNearest;
	ctx.cancelSnap = cancelSnap;

	function onScroll() {
		updateThumb();
		scheduleHide();
		if (!ctx.isDragging && !ctx.isProgrammatic) {
			scheduleSnap();
		}
	}
	scroller.addEventListener('scroll', onScroll, { passive: true });
	ctx.cleanupCallbacks.push(function () {
		scroller.removeEventListener('scroll', onScroll);
	});

    function onWheel(event) {
        if (!container.classList.contains('is-scrollable')) {
            return;
        }
        var dx = event.deltaX || 0;
        var dy = event.deltaY || 0;
        // Only react to true horizontal wheel/trackpad gestures.
        // Vertical wheel should scroll the page normally.
        if (Math.abs(dx) <= Math.abs(dy) || Math.abs(dx) < 1) {
            return; // let default vertical scroll pass through
        }
        event.preventDefault();
        ctx.isProgrammatic = false;
        cancelSnap();
        scroller.scrollLeft += dx;
        requestUpdate();
        scheduleHide();
        scheduleSnap();
    }
	scroller.addEventListener('wheel', onWheel, { passive: false });
	ctx.cleanupCallbacks.push(function () {
		scroller.removeEventListener('wheel', onWheel);
	});

	function onPointerDown(event) {
		if (!container.classList.contains('is-scrollable')) {
			return;
		}
		ctx.isDragging = true;
		ctx.isProgrammatic = false;
		cancelSnap();
		ctx.dragStartX = event.clientX;
		ctx.dragThumbStart = ctx.lastThumbLeft || 0;
		container.classList.add('is-dragging');
		ctx.thumb.classList.add('is-active');
		try {
			ctx.thumb.setPointerCapture(event.pointerId);
		} catch (err) {}
		event.preventDefault();
	}

	function onPointerMove(event) {
		if (!ctx.isDragging) {
			return;
		}
		var delta = event.clientX - ctx.dragStartX;
		var newLeft = Math.max(0, Math.min(ctx.maxThumbLeft, ctx.dragThumbStart + delta));
		ctx.lastThumbLeft = newLeft;
		ctx.thumb.style.transform = 'translateX(' + newLeft + 'px)';
		var ratio = ctx.maxThumbLeft ? newLeft / ctx.maxThumbLeft : 0;
		scroller.scrollLeft = ratio * ctx.maxScroll;
	}

	function onPointerEnd(event) {
		if (!ctx.isDragging) {
			return;
		}
		ctx.isDragging = false;
		container.classList.remove('is-dragging');
		ctx.thumb.classList.remove('is-active');
		if (event && event.pointerId != null) {
			try {
				ctx.thumb.releasePointerCapture(event.pointerId);
			} catch (err) {}
		}
		updateThumb();
		scheduleSnap();
	}

	ctx.thumb.addEventListener('pointerdown', onPointerDown);
	ctx.thumb.addEventListener('pointermove', onPointerMove);
	ctx.thumb.addEventListener('pointerup', onPointerEnd);
	ctx.thumb.addEventListener('pointercancel', onPointerEnd);
	ctx.cleanupCallbacks.push(function () {
		ctx.thumb.removeEventListener('pointerdown', onPointerDown);
		ctx.thumb.removeEventListener('pointermove', onPointerMove);
		ctx.thumb.removeEventListener('pointerup', onPointerEnd);
		ctx.thumb.removeEventListener('pointercancel', onPointerEnd);
	});

	if ('MutationObserver' in window) {
		var observer = new MutationObserver(function () {
			requestUpdate();
		});
		observer.observe(scroller, { childList: true, subtree: true, attributes: true, characterData: false });
		ctx.cleanupCallbacks.push(function () {
			observer.disconnect();
		});
	}

	function onResize() {
		requestUpdate();
	}
	window.addEventListener('resize', onResize);
	window.addEventListener('orientationchange', onResize);
	window.addEventListener('load', onResize);
	ctx.cleanupCallbacks.push(function () {
		window.removeEventListener('resize', onResize);
		window.removeEventListener('orientationchange', onResize);
		window.removeEventListener('load', onResize);
	});

	container._fvpsUpdateRail = requestUpdate;

	external.scrollTo = function (left, opts) {
		scrollTo(left, opts);
	};
	external.requestUpdate = requestUpdate;
	external.syncRail = requestUpdate;
	external.scheduleSnap = scheduleSnap;
	external.getScroller = function () {
		return scroller;
	};

	requestUpdate();
	if (typeof external.updateNavVisibility === 'function') {
		window.requestAnimationFrame(external.updateNavVisibility);
	}
}

	function syncCtaState(plan, topContainer, topButton, bottomButton) {
		if (!plan || !topContainer || !topButton || !bottomButton) {
			return;
		}
		if (bottomButton.hasAttribute('hidden')) {
			topContainer.setAttribute('hidden', '');
		} else {
			topContainer.removeAttribute('hidden');
		}

		var href = bottomButton.getAttribute('href');
		if (href) {
			topButton.setAttribute('href', href);
		} else {
			topButton.setAttribute('href', '#');
		}

		['target', 'rel'].forEach(function (attr) {
			var value = bottomButton.getAttribute(attr);
			if (value) {
				topButton.setAttribute(attr, value);
			} else {
				topButton.removeAttribute(attr);
			}
		});

		var sourceLabel = bottomButton.querySelector('[data-pwpl-cta-label]');
		var targetLabel = topButton.querySelector('span');
		if (sourceLabel && targetLabel) {
			targetLabel.textContent = sourceLabel.textContent || '';
		}
	}

	function setupCtaMirroring(plan) {
		if (!plan || plan._fvpsCtaInitialized) {
			return;
		}

		var topContainer = plan.querySelector('.fvps-card__cta-inline');
		var topButton = topContainer ? topContainer.querySelector('.fvps-button--inline') : null;
		var bottomButton = plan.querySelector('[data-pwpl-cta-button]');

		if (!topContainer || !topButton || !bottomButton) {
			return;
		}

		function apply() {
			syncCtaState(plan, topContainer, topButton, bottomButton);
		}

		apply();

		if ('MutationObserver' in window) {
			var observer = new MutationObserver(apply);
			observer.observe(bottomButton, { attributes: true, attributeFilter: ['hidden', 'href', 'target', 'rel'] });
			var labelNode = bottomButton.querySelector('[data-pwpl-cta-label]');
			if (labelNode) {
				observer.observe(labelNode, { childList: true, characterData: true, subtree: true });
			}
			plan._fvpsCtaObserver = observer;
		} else {
			ctaPlans.push({
				plan: plan,
				topContainer: topContainer,
				topButton: topButton,
				bottomButton: bottomButton
			});
		}

		plan._fvpsCtaInitialized = true;
	}

	function initPlans() {
		var plans = document.querySelectorAll(ROOT_SELECTOR + ' .pwpl-plan');
		if (!plans.length) {
			return;
		}
		plans.forEach(function (plan) {
			setupCtaMirroring(plan);
		});
		initPlanRails();
	}

	if (!('ResizeObserver' in window)) {
		var resizeTimeout;
		window.addEventListener('resize', function () {
			if (resizeTimeout) {
				window.clearTimeout(resizeTimeout);
			}
			resizeTimeout = window.setTimeout(function () {
				navRegistry.forEach(updateOverflowState);
			}, 60);
		});
	}

	if (!('MutationObserver' in window)) {
		var syncTimeout;
		var applyFallback = function () {
			if (syncTimeout) {
				window.clearTimeout(syncTimeout);
			}
			syncTimeout = window.setTimeout(function () {
				ctaPlans.forEach(function (entry) {
					syncCtaState(entry.plan, entry.topContainer, entry.topButton, entry.bottomButton);
				});
				initPlanRails();
			}, 75);
		};
		document.addEventListener('pwpl:updated', applyFallback);
		document.addEventListener('click', applyFallback);
	}
	document.addEventListener('pwpl:updated', initPlanRails);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initNavs();
			initPlans();
		});
	} else {
		initNavs();
		initPlans();
	}
})();

// FireVPS theme enhancements – tab overflow handling
(function () {
	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return;
	}

var ROOT_SELECTOR = '.pwpl-table--theme-firevps';
var navRegistry = [];
var ctaPlans = [];

function isTouchEnvironment() {
    try {
        if (window.matchMedia && (window.matchMedia('(hover: none)').matches || window.matchMedia('(pointer: coarse)').matches)) {
            return true;
        }
    } catch (e) {}
    return ('ontouchstart' in window) || (navigator.maxTouchPoints || 0) > 0;
}

function prefersReducedMotion() {
    try { return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch(e) { return false; }
}

function getTabScroller(nav) {
	if (!nav) {
		return null;
	}
	return nav.querySelector('[data-fvps-tab-viewport]') || nav;
}

function updateOverflowState(nav) {
	if (!nav || !nav.parentNode) {
		return;
	}
	var tablist = nav.querySelector('[data-fvps-tablist]') || nav.querySelector('.fvps-tablist');
	if (!tablist) {
		return;
	}
	var scroller = getTabScroller(nav);
	var navWidth = scroller ? scroller.clientWidth : nav.clientWidth;
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
	var tablist = nav.querySelector('[data-fvps-tablist]') || nav.querySelector('.fvps-tablist');
	if (!tablist) {
		return;
	}
	var scroller = getTabScroller(nav);

	if ('ResizeObserver' in window) {
		var observer = new ResizeObserver(function () {
			updateOverflowState(nav);
		});
		observer.observe(nav);
		if (scroller && scroller !== nav) {
			observer.observe(scroller);
		}
		observer.observe(tablist);
		nav._fvpsResizeObserver = observer;
	} else {
		navRegistry.push(nav);
	}

	updateOverflowState(nav);
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
		var scroller = getTabScroller(nav);
		ensureScrollRail(nav, scroller, { railClass: 'fvps-tabs-rail', minThumb: 32 });
        var arrows = ensureTabArrows(nav);
        var ctx = nav._fvpsRailCtx;
        if (ctx && arrows) {
            ctx.setControls(arrows.prevButton, arrows.nextButton, arrows.prevWrapper, arrows.nextWrapper);
            ctx.requestUpdate();
        }

        // Glass refraction blob (only if glass class present on table root)
        var tableEl = nav.closest(ROOT_SELECTOR);
        var enableGlass = tableEl && tableEl.classList.contains('pwpl-tabs-glass');
        if (enableGlass) {
            initGlassRefraction(nav);
        }

		// Auto-center on load only for non-touch to keep mobile left-anchored
		if (!isTouchEnvironment()) {
			centerActiveTab(nav, { behavior: 'auto' });
		}

		// Center the clicked tab (anticipate activation)
		nav.addEventListener('click', function (evt) {
			var btn = evt.target && evt.target.closest ? evt.target.closest('.pwpl-tab') : null;
			if (!btn || !nav.contains(btn)) { return; }
			if (typeof window.requestAnimationFrame === 'function') {
				window.requestAnimationFrame(function(){ centerTab(nav, btn, { behavior: 'smooth' }); });
			} else {
				centerTab(nav, btn, { behavior: 'smooth' });
			}
		});
	});
}

function initGlassRefraction(nav){
    var prefersReduced = false;
    try { prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch(e){}
    if (prefersReduced) { return; }
    var scroller = getTabScroller(nav) || nav;
    var raf = null;
    function setFromEvent(e){
        var rect = scroller.getBoundingClientRect();
        var x = (e.clientX - rect.left) / Math.max(rect.width,1) * 100;
        var y = (e.clientY - rect.top) / Math.max(rect.height,1) * 100;
        y = Math.min(60, Math.max(20, y));
        nav.style.setProperty('--glass-x', x + '%');
        nav.style.setProperty('--glass-y', y + '%');
    }
    function onMove(e){
        if (raf) return; raf = requestAnimationFrame(function(){ raf = null; setFromEvent(e); });
    }
    function onScroll(){
        var rect = scroller.getBoundingClientRect();
        var x = ( (rect.width/2) / Math.max(rect.width,1) ) * 100;
        nav.style.setProperty('--glass-x', x + '%');
    }
    scroller.addEventListener('pointermove', onMove, { passive: true });
    scroller.addEventListener('mousemove', onMove, { passive: true });
    scroller.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

function ensureTabArrows(nav) {
	if (!nav) {
		return null;
	}
	if (nav._fvpsTabsArrows) {
		return nav._fvpsTabsArrows;
	}
	var prevWrapper = document.createElement('div');
	prevWrapper.className = 'fvps-tabs-nav fvps-tabs-nav--prev';
	var prevBtn = document.createElement('button');
	prevBtn.type = 'button';
	prevBtn.className = 'fvps-tabs-nav__btn';
	prevBtn.setAttribute('aria-label', 'Scroll previous');
	prevBtn.innerHTML = '&#10094;';
	prevBtn.disabled = true;
	prevBtn.setAttribute('aria-disabled', 'true');
	prevWrapper.hidden = true;
	prevWrapper.appendChild(prevBtn);

	var nextWrapper = document.createElement('div');
	nextWrapper.className = 'fvps-tabs-nav fvps-tabs-nav--next';
	var nextBtn = document.createElement('button');
	nextBtn.type = 'button';
	nextBtn.className = 'fvps-tabs-nav__btn';
	nextBtn.setAttribute('aria-label', 'Scroll next');
	nextBtn.innerHTML = '&#10095;';
	nextBtn.disabled = true;
	nextBtn.setAttribute('aria-disabled', 'true');
	nextWrapper.hidden = true;
	nextWrapper.appendChild(nextBtn);

	nav.appendChild(prevWrapper);
	nav.appendChild(nextWrapper);

	var scroller = getTabScroller(nav);
	function handle(direction) {
		var ctx = nav._fvpsRailCtx;
		var targetScroller = ctx && ctx.scroller ? ctx.scroller : (scroller || nav);
		if (!targetScroller) {
			return;
		}
		var viewportWidth = targetScroller.clientWidth || nav.clientWidth || 0;
		var step = Math.max(viewportWidth * 0.65, 96);
		var current = targetScroller.scrollLeft;
		var target = direction === 'next' ? current + step : current - step;
		if (ctx && typeof ctx.scrollTo === 'function') {
			ctx.scrollTo(target, { behavior: 'smooth' });
		} else if (typeof targetScroller.scrollTo === 'function') {
			try {
				targetScroller.scrollTo({ left: target, behavior: 'smooth' });
			} catch (err) {
				targetScroller.scrollLeft = target;
			}
		} else {
			targetScroller.scrollLeft = target;
		}
	}

	prevBtn.addEventListener('click', function () { handle('prev'); });
	nextBtn.addEventListener('click', function () { handle('next'); });

	var arrows = {
		prevWrapper: prevWrapper,
		nextWrapper: nextWrapper,
		prevButton: prevBtn,
		nextButton: nextBtn
	};
	nav._fvpsTabsArrows = arrows;
	return arrows;
}

	function centerActiveTab(nav, opts) {
		var active = nav.querySelector('.pwpl-tab.is-active') || nav.querySelector('.pwpl-tab[aria-pressed="true"]');
		if (!active) { return; }
		centerTab(nav, active, opts);
	}

function centerTab(nav, tab, opts) {
	if (!nav || !tab) { return; }
	var behavior = (opts && opts.behavior) || 'smooth';

	var ctx = nav._fvpsRailCtx || null;
	var scroller = ctx && ctx.scroller ? ctx.scroller : getTabScroller(nav);
	var targetNode = scroller || nav;
	if (!targetNode) { return; }

	// Prefer geometry-based centering for accuracy
	var rectNav = targetNode.getBoundingClientRect();
	var rectTab = tab.getBoundingClientRect();
	var delta = (rectTab.left - rectNav.left) - (rectNav.width - rectTab.width) / 2;
	var target = (targetNode.scrollLeft || 0) + delta;
	var maxScroll = Math.max(targetNode.scrollWidth - targetNode.clientWidth, 0);
	target = Math.max(0, Math.min(maxScroll, target));

	// If the tab supports scrollIntoView with inline:center, try it first
	var used = false;
	try {
		if (typeof tab.scrollIntoView === 'function' && behavior !== 'auto') {
			tab.scrollIntoView({ behavior: behavior, inline: 'center', block: 'nearest' });
			used = true;
		}
	} catch (e) {
		used = false;
	}

	if (!used) {
		if (ctx && typeof ctx.scrollTo === 'function') {
			ctx.scrollTo(target, { behavior: behavior });
			return;
		}
		var enh = nav._pwplScrollEnhancer || (scroller && scroller._pwplScrollEnhancer);
		if (enh && typeof enh.scrollTo === 'function') {
			enh.scrollTo(target, { behavior: behavior });
		} else if (typeof targetNode.scrollTo === 'function') {
			try {
				targetNode.scrollTo({ left: target, behavior: behavior });
			} catch (err) {
				targetNode.scrollLeft = target;
			}
		} else {
			targetNode.scrollLeft = target;
		}
	}
}

function ensurePlanNavControls(wrapper) {
	if (!wrapper) {
		return null;
	}
	var prevWrapper = wrapper.querySelector('.pwpl-plan-nav--prev') || null;
	var nextWrapper = wrapper.querySelector('.pwpl-plan-nav--next') || null;
	var prevButton = prevWrapper ? prevWrapper.querySelector('.pwpl-plan-nav__btn') : null;
	var nextButton = nextWrapper ? nextWrapper.querySelector('.pwpl-plan-nav__btn') : null;

	if (prevWrapper && !prevWrapper._fvpsNavPrepared) {
		prevWrapper.hidden = true;
		prevWrapper._fvpsNavPrepared = true;
	}
	if (nextWrapper && !nextWrapper._fvpsNavPrepared) {
		nextWrapper.hidden = true;
		nextWrapper._fvpsNavPrepared = true;
	}

	if (prevButton && !prevButton._fvpsBound) {
		prevButton._fvpsBound = true;
		prevButton.disabled = true;
		prevButton.setAttribute('aria-disabled', 'true');
		prevButton.addEventListener('click', function () {
			var ctx = wrapper._fvpsRailCtx;
			if (!ctx || !ctx.scroller) { return; }
			var scroller = ctx.scroller;
			var step = Math.max(scroller.clientWidth * 0.8, 160);
			ctx.scrollTo(scroller.scrollLeft - step, { behavior: 'smooth' });
		});
	}

	if (nextButton && !nextButton._fvpsBound) {
		nextButton._fvpsBound = true;
		nextButton.disabled = true;
		nextButton.setAttribute('aria-disabled', 'true');
		nextButton.addEventListener('click', function () {
			var ctx = wrapper._fvpsRailCtx;
			if (!ctx || !ctx.scroller) { return; }
			var scroller = ctx.scroller;
			var step = Math.max(scroller.clientWidth * 0.8, 160);
			ctx.scrollTo(scroller.scrollLeft + step, { behavior: 'smooth' });
		});
	}

	if (!prevButton && !nextButton) {
		return null;
	}

	return {
		prevWrapper: prevWrapper,
		nextWrapper: nextWrapper,
		prevButton: prevButton,
		nextButton: nextButton
	};
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
				snap: false,
				snapDelay: 180,
				itemsProvider: function (node) {
					return Array.from(node.querySelectorAll('.pwpl-plan')).filter(function (item) {
						return item && item.offsetParent !== null && !item.hidden && !item.classList.contains('pwpl-hidden');
					});
				}
			});
			var planControls = ensurePlanNavControls(wrapper);
			var ctx = wrapper._fvpsRailCtx;
			if (ctx && planControls) {
				ctx.setControls(planControls.prevButton, planControls.nextButton, planControls.prevWrapper, planControls.nextWrapper);
				ctx.requestUpdate();
			}

			// One-time rail hint on first view
			try {
				var seenKey = 'fvpsPlansRailHintSeen';
				if (!localStorage.getItem(seenKey)) {
					wrapper.classList.add('is-user-scrolling');
					setTimeout(function(){
						wrapper.classList.remove('is-user-scrolling');
						localStorage.setItem(seenKey, '1');
					}, 1500);
				}
			} catch (e) {}
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
	container._pwplScrollEnhancer = external;

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
		prevButton: options.prevButton || null,
		nextButton: options.nextButton || null,
		prevWrapper: options.prevWrapper || null,
		nextWrapper: options.nextWrapper || null,
		cleanupCallbacks: []
	};

	function applyControlState() {
		var isScrollable = ctx.maxScroll > 1;
		var atStart = scroller.scrollLeft <= 1;
		var atEnd = (ctx.maxScroll - scroller.scrollLeft) <= 1;
		container.classList.toggle('at-start', atStart);
		container.classList.toggle('at-end', atEnd);
		if (ctx.prevWrapper) {
			ctx.prevWrapper.hidden = !isScrollable;
		}
		if (ctx.nextWrapper) {
			ctx.nextWrapper.hidden = !isScrollable;
		}
		if (ctx.prevButton) {
			var disablePrev = !isScrollable || atStart;
			ctx.prevButton.disabled = disablePrev;
			ctx.prevButton.setAttribute('aria-disabled', disablePrev ? 'true' : 'false');
			ctx.prevButton.classList.toggle('is-disabled', disablePrev);
		}
		if (ctx.nextButton) {
			var disableNext = !isScrollable || atEnd;
			ctx.nextButton.disabled = disableNext;
			ctx.nextButton.setAttribute('aria-disabled', disableNext ? 'true' : 'false');
			ctx.nextButton.classList.toggle('is-disabled', disableNext);
		}
		return { isScrollable: isScrollable, atStart: atStart, atEnd: atEnd };
	}

	function setControls(prevButton, nextButton, prevWrapper, nextWrapper) {
		ctx.prevButton = prevButton || null;
		ctx.nextButton = nextButton || null;
		ctx.prevWrapper = prevWrapper || (ctx.prevButton && ctx.prevButton.closest ? ctx.prevButton.closest('.fvps-tabs-nav, .pwpl-plan-nav') : ctx.prevWrapper) || null;
		ctx.nextWrapper = nextWrapper || (ctx.nextButton && ctx.nextButton.closest ? ctx.nextButton.closest('.fvps-tabs-nav, .pwpl-plan-nav') : ctx.nextWrapper) || null;
		if (ctx.prevButton) {
			ctx.prevButton.setAttribute('aria-disabled', ctx.prevButton.disabled ? 'true' : 'false');
		}
		if (ctx.nextButton) {
			ctx.nextButton.setAttribute('aria-disabled', ctx.nextButton.disabled ? 'true' : 'false');
		}
		applyControlState();
	}

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
			if (ctx.external.setControls === setControls) {
				delete ctx.external.setControls;
			}
			if (ctx.external.applyControlState === applyControlState) {
				delete ctx.external.applyControlState;
			}
		}
		ctx.prevButton = null;
		ctx.nextButton = null;
		ctx.prevWrapper = null;
		ctx.nextWrapper = null;
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
			applyControlState();
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
		applyControlState();
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
		applyControlState();
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
				applyControlState();
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
					applyControlState();
				}
			});
			return;
		}
	if (behavior === 'auto') {
		ctx.isProgrammatic = false;
		requestUpdate();
		scheduleSnap();
		applyControlState();
		return;
	}
		var durationEstimate = opts.duration || ctx.options.animationDuration || 320;
	window.setTimeout(function () {
		ctx.isProgrammatic = false;
		requestUpdate();
		scheduleSnap();
		applyControlState();
	}, durationEstimate + (ctx.options.snapDelay || 180));
}

	ctx.updateThumb = updateThumb;
	ctx.updateMetrics = updateMetrics;
	ctx.requestUpdate = requestUpdate;
	ctx.scrollTo = scrollTo;
	ctx.scheduleSnap = scheduleSnap;
	ctx.snapToNearest = snapToNearest;
	ctx.cancelSnap = cancelSnap;
	ctx.applyControlState = applyControlState;
	ctx.setControls = setControls;
	setControls(ctx.prevButton, ctx.nextButton, ctx.prevWrapper, ctx.nextWrapper);

	function onScroll() {
		updateThumb();
		applyControlState();
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
        applyControlState();
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
		applyControlState();
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
	external.setControls = setControls;
	external.applyControlState = applyControlState;
	external.getScroller = function () {
		return scroller;
	};
	if (typeof external.updateNavVisibility !== 'function') {
		external.updateNavVisibility = applyControlState;
	}

	requestUpdate();
	applyControlState();
	if (typeof external.updateNavVisibility === 'function') {
		window.requestAnimationFrame(external.updateNavVisibility);
	}
}

function syncCtaState(plan, topContainer, topButton, bottomButton, bottomContainer) {
    if (!plan || !topContainer || !topButton || !bottomButton) {
        return;
    }
    if (bottomButton.hasAttribute('hidden')) {
        topContainer.setAttribute('hidden', '');
        plan.classList.remove('fvps-card--inline-cta');
        if (bottomContainer) {
            bottomContainer.removeAttribute('hidden');
        }
    } else {
        topContainer.removeAttribute('hidden');
        plan.classList.add('fvps-card--inline-cta');
        if (bottomContainer) {
            bottomContainer.setAttribute('hidden', '');
        }
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
    var bottomContainer = plan.querySelector('.fvps-card__cta');
    var bottomButton = plan.querySelector('[data-pwpl-cta-button]');

    if (!topContainer || !topButton || !bottomButton) {
        return;
    }

    function apply() {
        syncCtaState(plan, topContainer, topButton, bottomButton, bottomContainer);
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

    // One-time CTA sheen on view (desktop only)
    function initCtaOnView(root){
        if (!root) return;
        if (isTouchEnvironment() || prefersReducedMotion()) return;
        var buttons = root.querySelectorAll('.fvps-button, .fvps-button--inline');
        if (!buttons.length) return;
        var seen = new WeakSet();
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (!entry.isIntersecting) return;
                var btn = entry.target; if (seen.has(btn)) return; seen.add(btn);
                btn.classList.add('fvps-cta--onview');
                setTimeout(function(){ btn.classList.remove('fvps-cta--onview'); }, 1200);
            });
        }, { threshold: 0.6 });
        buttons.forEach(function(b){ io.observe(b); });
    }

    // Sticky mobile summary bar when CTA off-screen
    function initStickyBar(root){
        if (!root || root.getAttribute('data-sticky-cta') !== 'on') return;
        var isMobile = (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) || isTouchEnvironment();
        if (!isMobile) return;
        var bar = document.createElement('div');
        bar.className = 'fvps-sticky-cta';
        bar.innerHTML = '<div class="fvps-sticky-cta__meta"><div class="fvps-sticky-cta__title"></div><div class="fvps-sticky-cta__price"></div></div><a class="fvps-sticky-cta__btn" href="#"></a>';
        root.appendChild(bar);
        var titleEl = bar.querySelector('.fvps-sticky-cta__title');
        var priceEl = bar.querySelector('.fvps-sticky-cta__price');
        var btnEl = bar.querySelector('.fvps-sticky-cta__btn');

        function ctaVisible(){
            var ctAs = root.querySelectorAll('.fvps-button, .fvps-button--inline');
            var vh = window.innerHeight || document.documentElement.clientHeight;
            for (var i=0;i<ctAs.length;i++){
                var r = ctAs[i].getBoundingClientRect();
                if (r.top >= 0 && r.bottom <= vh) return true;
            }
            return false;
        }
        function nearestPlan(){
            var plans = Array.from(root.querySelectorAll('.pwpl-plan'));
            var best = null, bestDelta = Infinity;
            for (var i=0;i<plans.length;i++){
                var rect = plans[i].getBoundingClientRect();
                if (rect.bottom < 80) continue;
                var d = Math.abs(rect.top - 80);
                if (d < bestDelta) { best = plans[i]; bestDelta = d; }
            }
            return best;
        }
        function refresh(){
            if (ctaVisible()) { bar.classList.remove('is-visible'); return; }
            var plan = nearestPlan(); if (!plan) { bar.classList.remove('is-visible'); return; }
            var t = plan.querySelector('.pwpl-plan__title');
            var p = plan.querySelector('[data-pwpl-price]');
            var cta = plan.querySelector('.fvps-button[hidden], .fvps-button--inline[hidden]') ? null : (plan.querySelector('.fvps-button--inline') || plan.querySelector('.fvps-button'));
            titleEl.textContent = t ? t.textContent.trim() : '';
            priceEl.textContent = p ? p.textContent.trim() : '';
            var href = cta && !cta.hasAttribute('aria-disabled') ? cta.getAttribute('href') : '';
            var label = cta ? cta.textContent.trim() : '';
            btnEl.textContent = label || 'Select';
            if (href) { btnEl.setAttribute('href', href); btnEl.removeAttribute('aria-disabled'); }
            else { btnEl.removeAttribute('href'); btnEl.setAttribute('aria-disabled','true'); }
            bar.classList.add('is-visible');
        }
        var onScroll = function(){ refresh(); };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        setTimeout(refresh, 60);
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

// Re-center tabs on Planify updates (e.g., when active pill changes)
document.addEventListener('pwpl:updated', function(){
	var navs = document.querySelectorAll(ROOT_SELECTOR + ' .fvps-dimension-nav');
	navs.forEach(function(nav){
		var arrows = ensureTabArrows(nav);
		var ctx = nav._fvpsRailCtx;
		if (ctx && arrows) {
			ctx.setControls(arrows.prevButton, arrows.nextButton, arrows.prevWrapper, arrows.nextWrapper);
			ctx.requestUpdate();
		}
		if (!isTouchEnvironment()) {
			centerActiveTab(nav, { behavior: 'smooth' });
		}
	});
});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initNavs();
			initPlans();
			var tables = document.querySelectorAll(ROOT_SELECTOR);
			tables.forEach(function(t){ initCtaOnView(t); initStickyBar(t); });
		});
	} else {
		initNavs();
		initPlans();
		var tables = document.querySelectorAll(ROOT_SELECTOR);
		tables.forEach(function(t){ initCtaOnView(t); initStickyBar(t); });
	}
})();

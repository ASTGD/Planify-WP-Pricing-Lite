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
	ensureTabsRail(nav);
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
		ensureTabsRail(nav);
		});
	}

	function ensureTabsRail(nav) {
		var ctx = nav._fvpsRailCtx;
		if (!ctx) {
			var rail = nav.querySelector('.fvps-tabs-rail');
			if (!rail) {
				rail = document.createElement('div');
				rail.className = 'fvps-tabs-rail';
				rail.setAttribute('aria-hidden', 'true');
				var track = document.createElement('div');
				track.className = 'fvps-tabs-rail__track';
				var thumb = document.createElement('div');
				thumb.className = 'fvps-tabs-rail__thumb';
				track.appendChild(thumb);
				rail.appendChild(track);
				nav.appendChild(rail);
			}

			var trackNode = rail.querySelector('.fvps-tabs-rail__track');
			var thumbNode = rail.querySelector('.fvps-tabs-rail__thumb');
			ctx = nav._fvpsRailCtx = {
				rail: rail,
				track: trackNode,
				thumb: thumbNode,
				isDragging: false,
				hideTimer: null,
				rafPending: false,
				maxScroll: 0,
				maxThumbLeft: 0,
				thumbWidth: 0,
				lastThumbLeft: 0
			};

			function updateThumb() {
				if (!nav.classList.contains('is-scrollable')) {
					return;
				}
				var scrollLeft = nav.scrollLeft;
				var thumbLeft = ctx.maxScroll ? (scrollLeft / ctx.maxScroll) * ctx.maxThumbLeft : 0;
				thumbLeft = Math.max(0, Math.min(ctx.maxThumbLeft, thumbLeft));
				ctx.lastThumbLeft = thumbLeft;
				ctx.thumb.style.width = ctx.thumbWidth + 'px';
				ctx.thumb.style.transform = 'translateX(' + thumbLeft + 'px)';
			}

			function updateMetrics() {
				var scrollWidth = nav.scrollWidth;
				var clientWidth = nav.clientWidth;
				ctx.maxScroll = Math.max(scrollWidth - clientWidth, 0);
				var isScrollable = ctx.maxScroll > 1;
				nav.classList.toggle('is-scrollable', isScrollable);
				nav.classList.toggle('is-overflowing', isScrollable);
				ctx.rail.hidden = !isScrollable;
				if (!isScrollable) {
					nav.classList.remove('is-user-scrolling');
					nav.classList.remove('is-dragging');
					if (ctx.hideTimer) {
						clearTimeout(ctx.hideTimer);
						ctx.hideTimer = null;
					}
					return;
				}
				var trackWidth = ctx.track.clientWidth;
				if (!trackWidth) {
					requestUpdate();
					return;
				}
				var ratio = clientWidth / scrollWidth;
				var minThumb = 32;
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

			ctx.updateMetrics = updateMetrics;
			ctx.updateThumb = updateThumb;
			ctx.requestUpdate = requestUpdate;

			var onScroll = function () {
				if (!ctx.isDragging) {
					updateThumb();
				}
				if (!nav.classList.contains('is-scrollable')) {
					return;
				}
				nav.classList.add('is-user-scrolling');
				if (ctx.hideTimer) {
					clearTimeout(ctx.hideTimer);
				}
				ctx.hideTimer = setTimeout(function () {
					nav.classList.remove('is-user-scrolling');
				}, 600);
			};

			nav.addEventListener('scroll', onScroll, { passive: true });

			nav.addEventListener('wheel', function (event) {
				if (!nav.classList.contains('is-scrollable')) {
					return;
				}
				var dominant = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
				if (!dominant) {
					return;
				}
				event.preventDefault();
				nav.scrollLeft += dominant;
			}, { passive: false });

			ctx.thumb.addEventListener('pointerdown', function (event) {
				if (!nav.classList.contains('is-scrollable')) {
					return;
				}
				ctx.isDragging = true;
				ctx.dragStartX = event.clientX;
				ctx.dragThumbStart = ctx.lastThumbLeft || 0;
				nav.classList.add('is-dragging');
				ctx.thumb.classList.add('is-active');
				try {
					ctx.thumb.setPointerCapture(event.pointerId);
				} catch (e) {}
				event.preventDefault();
			});

			ctx.thumb.addEventListener('pointermove', function (event) {
				if (!ctx.isDragging) {
					return;
				}
				var delta = event.clientX - ctx.dragStartX;
				var newLeft = Math.max(0, Math.min(ctx.maxThumbLeft, ctx.dragThumbStart + delta));
				ctx.lastThumbLeft = newLeft;
				ctx.thumb.style.transform = 'translateX(' + newLeft + 'px)';
				var ratio = ctx.maxThumbLeft ? newLeft / ctx.maxThumbLeft : 0;
				nav.scrollLeft = ratio * ctx.maxScroll;
			});

			var endDrag = function (event) {
				if (!ctx.isDragging) {
					return;
				}
				ctx.isDragging = false;
				nav.classList.remove('is-dragging');
				ctx.thumb.classList.remove('is-active');
				if (ctx.hideTimer) {
					clearTimeout(ctx.hideTimer);
					ctx.hideTimer = null;
				}
				nav.classList.remove('is-user-scrolling');
				if (event && event.pointerId != null) {
					try {
						ctx.thumb.releasePointerCapture(event.pointerId);
					} catch (e) {}
				}
				updateThumb();
			};

			ctx.thumb.addEventListener('pointerup', endDrag);
			ctx.thumb.addEventListener('pointercancel', endDrag);

			if ('MutationObserver' in window) {
				ctx.observer = new MutationObserver(function () {
					requestUpdate();
				});
				ctx.observer.observe(nav, { childList: true, subtree: true });
			}

			ctx.boundResize = function () {
				requestUpdate();
			};
			window.addEventListener('resize', ctx.boundResize);
			window.addEventListener('orientationchange', ctx.boundResize);
			window.addEventListener('load', ctx.boundResize);

			requestUpdate();
		} else if (typeof ctx.requestUpdate === 'function') {
			ctx.requestUpdate();
		}
		nav._fvpsUpdateRail = ctx.requestUpdate || ctx.updateMetrics;
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
			}, 75);
		};
		document.addEventListener('pwpl:updated', applyFallback);
		document.addEventListener('click', applyFallback);
	}

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

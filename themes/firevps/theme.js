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

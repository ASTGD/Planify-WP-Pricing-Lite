// FireVPS theme enhancements – tab overflow handling
(function () {
	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return;
	}

	var ROOT_SELECTOR = '.pwpl-table--theme-firevps';
	var navRegistry = [];

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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initNavs);
	} else {
		initNavs();
	}
})();

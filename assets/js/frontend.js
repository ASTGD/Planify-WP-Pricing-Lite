// Planify WP Pricing Lite - frontend interactions
(function(){
    function __(text) {
        if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') {
            return window.wp.i18n.__(text, 'planify-wp-pricing-lite');
        }
        return text;
    }

    const currency = (window.PWPL_Frontend && window.PWPL_Frontend.currency) || {
        symbol: '$',
        position: 'left',
        thousand_sep: ',',
        decimal_sep: '.',
        price_decimals: 2
    };

    function formatPrice(amount) {
        if (amount === '' || amount === null || amount === undefined) {
            return '';
        }
        const num = parseFloat(String(amount).replace(/[^0-9.\-]/g, ''));
        if (isNaN(num)) {
            return '';
        }
        const decimals = typeof currency.price_decimals === 'number' ? currency.price_decimals : 2;
        const parts = num.toFixed(decimals).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_sep || ',');
        let formatted = parts.join(currency.decimal_sep || '.');
        switch (currency.position) {
            case 'right':
                return formatted + currency.symbol;
            case 'left_space':
                return currency.symbol + ' ' + formatted;
            case 'right_space':
                return formatted + ' ' + currency.symbol;
            case 'left':
            default:
                return currency.symbol + formatted;
        }
    }

    function resolveVariant(variants, selection) {
        if (!Array.isArray(variants) || !variants.length) {
            return null;
        }
        const dimensions = ['platform', 'period', 'location'];
        let best = null;
        let bestScore = -1;
        variants.forEach(function(variant){
            if (!variant || typeof variant !== 'object') {
                return;
            }
            let score = 0;
            let match = true;
            dimensions.forEach(function(dimension){
                if (!match) { return; }
                const selected = selection[dimension];
                const value = variant[dimension] || '';
                if (selected) {
                    if (value && value === selected) {
                        score += 2;
                    } else if (!value) {
                        score += 1;
                    } else {
                        match = false;
                    }
                }
            });
            if (match && score > bestScore) {
                bestScore = score;
                best = variant;
            }
        });
        return best || variants[0];
    }

    function buildPriceHTML(variant) {
        if (!variant) {
            return '<span class="pwpl-plan__price--empty">' + __( 'Contact us' ) + '</span>';
        }
        const price = variant.price || '';
        const sale = variant.sale_price || '';
        if (!price && !sale) {
            return '<span class="pwpl-plan__price--empty">' + __( 'Contact us' ) + '</span>';
        }
        const formattedPrice = price ? formatPrice(price) : '';
        const formattedSale = sale ? formatPrice(sale) : '';
        if (formattedSale && formattedPrice) {
            return '<span class="pwpl-plan__price-sale">' + formattedSale + '</span>' +
                   '<span class="pwpl-plan__price-original">' + formattedPrice + '</span>';
        }
        const display = formattedPrice || formattedSale;
        return '<span class="pwpl-plan__price">' + display + '</span>';
    }

    function getVariants(plan) {
        if (!plan) {
            return [];
        }
        if (!plan.__pwplVariants) {
            try {
                plan.__pwplVariants = JSON.parse(plan.dataset.variants || '[]');
            } catch (e) {
                plan.__pwplVariants = [];
            }
        }
        return plan.__pwplVariants;
    }

    function currentSelection(table) {
        const selection = {};
        ['platform', 'period', 'location'].forEach(function(dimension){
            selection[dimension] = table.dataset['active' + dimension.charAt(0).toUpperCase() + dimension.slice(1)] || '';
            if (!selection[dimension]) {
                const activeButton = table.querySelector('.pwpl-dimension-nav[data-dimension="' + dimension + '"] .pwpl-tab.is-active');
                if (activeButton) {
                    selection[dimension] = activeButton.dataset.value || '';
                }
            }
        });
        return selection;
    }

    function updateTable(table) {
        const selection = currentSelection(table);
        const plans = table.querySelectorAll('.pwpl-plan');
        plans.forEach(function(plan){
            const variants = getVariants(plan);
            const best = resolveVariant(variants, selection);
            const priceEl = plan.querySelector('[data-pwpl-price]');
            if (priceEl) {
                priceEl.innerHTML = buildPriceHTML(best);
            }
        });
    }

    function setActive(table, dimension, value, button) {
        table.dataset['active' + dimension.charAt(0).toUpperCase() + dimension.slice(1)] = value;
        const nav = table.querySelector('.pwpl-dimension-nav[data-dimension="' + dimension + '"]');
        if (!nav) { return; }
        nav.querySelectorAll('.pwpl-tab').forEach(function(tab){
            tab.classList.remove('is-active');
            tab.setAttribute('aria-pressed', 'false');
        });
        if (button) {
            button.classList.add('is-active');
            button.setAttribute('aria-pressed', 'true');
        }
        updateTable(table);
    }

    document.addEventListener('click', function(event){
        const button = event.target.closest('.pwpl-tab');
        if (!button) {
            return;
        }
        const nav = button.closest('.pwpl-dimension-nav');
        if (!nav) {
            return;
        }
        const table = nav.closest('.pwpl-table');
        if (!table) {
            return;
        }
        event.preventDefault();
        const dimension = nav.dataset.dimension;
        const value = button.dataset.value || '';
        setActive(table, dimension, value, button);
    });

    document.querySelectorAll('.pwpl-table').forEach(function(table){
        initPlanRail(table);
        updateTable(table);
    });

    function initPlanRail(table) {
        const rail = table.querySelector('.pwpl-plan-grid');
        const prev = table.querySelector('.pwpl-plan-nav--prev');
        const next = table.querySelector('.pwpl-plan-nav--next');

        if (!rail || !prev || !next) {
            return;
        }

        function updateNavVisibility() {
            const maxScroll = Math.max(rail.scrollWidth - rail.clientWidth, 0);
            prev.hidden = rail.scrollLeft <= 0;
            next.hidden = rail.scrollLeft >= maxScroll - 1;
        }

        function scrollRail(direction) {
            const delta = rail.clientWidth * 0.85 * (direction === 'next' ? 1 : -1);
            rail.scrollBy({ left: delta, behavior: 'smooth' });
        }

        prev.addEventListener('click', function(){ scrollRail('prev'); });
        next.addEventListener('click', function(){ scrollRail('next'); });

        rail.addEventListener('scroll', updateNavVisibility, { passive: true });
        window.addEventListener('resize', updateNavVisibility);

        updateNavVisibility();
    }
})();

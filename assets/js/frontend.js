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

        // Determine if there is a real discount (numeric and sale < base)
        const priceNum = (price !== '' && price !== null) ? parseFloat(price) : NaN;
        const saleNum = (sale !== '' && sale !== null) ? parseFloat(sale) : NaN;
        const hasDiscount = !Number.isNaN(priceNum) && !Number.isNaN(saleNum) && priceNum > 0 && saleNum >= 0 && saleNum < priceNum;

        // Infer a compact billing unit from the variant period
        let unit = '';
        const period = (variant && (variant.period || '')).toString().toLowerCase();
        if (/month|monthly|\bmo\b/.test(period)) unit = ' /mo';
        else if (/year|annual|annually|\byr\b/.test(period)) unit = ' /yr';
        else if (/day|daily/.test(period)) unit = ' /day';
        else if (/hour|hr/.test(period)) unit = ' /hr';

        if (hasDiscount && formattedSale && formattedPrice) {
            const pct = Math.round(((priceNum - saleNum) / priceNum) * 100);
            const badge = pct > 0
                ? '<span class="fvps-price-badge" aria-label="' + pct + '% off">' + pct + '% OFF</span>'
                : '';
            // Split currency pieces from formattedSale for typography
            const m = formattedSale.match(/^([^\d\-]*)([0-9][0-9\.,]*)\s*([^\d]*)$/);
            const pfx = m ? (m[1] || '').trim() : '';
            const val = m ? (m[2] || formattedSale) : formattedSale;
            const sfx = m ? (m[3] || '').trim() : '';
            const saleHtml = '<span class="pwpl-plan__price-sale">'
              + (pfx ? '<span class="pwpl-price-currency pwpl-currency--prefix">' + pfx + '</span>' : '')
              + '<span class="pwpl-price-value">' + val + '</span>'
              + (sfx ? '<span class="pwpl-price-currency pwpl-currency--suffix">' + sfx + '</span>' : '')
              + '</span>'
              + (unit ? '<span class="pwpl-price-unit">' + unit + '</span>' : '');
            return '<span class="pwpl-plan__price-original">' + formattedPrice + '</span>' + badge + saleHtml;
        }

        const display = formattedSale || formattedPrice;
        const m = display.match(/^([^\d\-]*)([0-9][0-9\.,]*)\s*([^\d]*)$/);
        const pfx = m ? (m[1] || '').trim() : '';
        const val = m ? (m[2] || display) : display;
        const sfx = m ? (m[3] || '').trim() : '';
        const single = (pfx ? '<span class="pwpl-price-currency pwpl-currency--prefix">' + pfx + '</span>' : '')
            + '<span class="pwpl-price-value">' + val + '</span>'
            + (sfx ? '<span class="pwpl-price-currency pwpl-currency--suffix">' + sfx + '</span>' : '');
        return '<span class="pwpl-plan__price">' + single + '</span>' + (unit ? '<span class="pwpl-price-unit">' + unit + '</span>' : '');
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

    function parseAllowedPlatforms(table) {
        if (!table) {
            return [];
        }
        const raw = table.dataset.allowedPlatforms || '';
        if (!raw) {
            return [];
        }
        return raw.split(',').map(function(item){
            return item.trim();
        }).filter(function(item){ return !!item; });
    }

    function filterPlansByPlatform(table, platform) {
        if (!table) {
            return;
        }
        const plans = table.querySelectorAll('.pwpl-plan');
        if (!plans.length) {
            return;
        }
        const slug = platform || '';
        table.dataset.activePlatform = slug;
        plans.forEach(function(plan){
            const data = (plan.dataset.platforms || '').trim();
            if (!slug || !data || data === '*') {
                plan.classList.remove('pwpl-hidden');
                return;
            }
            const platforms = data.split(',').map(function(item){ return item.trim(); }).filter(function(item){ return !!item; });
            const matches = platforms.indexOf(slug) !== -1;
            plan.classList.toggle('pwpl-hidden', !matches);
        });
    }

    // Apply availability filtering to period tabs and return the active period slug.
    function filterPeriodsByPlatform(table, platform) {
        const nav = table.querySelector('.pwpl-dimension-nav[data-dimension="period"]');
        if (!nav) {
            return '';
        }
        const availability = table.dataset.availability ? safeParseJSON(table.dataset.availability, {}) : {};
        const periodsByPlatform = availability.periodsByPlatform || {};
        const allowedPeriods = platform && periodsByPlatform[platform] ? periodsByPlatform[platform] : [];
        const tabs = nav.querySelectorAll('.pwpl-tab');
        let activeSlug = table.dataset.activePeriod || '';
        tabs.forEach(function(tab){
            const slug = tab.dataset.value || '';
            const allowed = !allowedPeriods.length || allowedPeriods.indexOf(slug) !== -1;
            tab.classList.toggle('pwpl-hidden', !allowed);
            if (!allowed && tab.classList.contains('is-active')) {
                tab.classList.remove('is-active');
                tab.setAttribute('aria-pressed', 'false');
                activeSlug = '';
            }
        });

        // Ensure there is an active period; fall back to first visible.
        if (!activeSlug) {
            const firstVisible = nav.querySelector('.pwpl-tab:not(.pwpl-hidden)');
            if (firstVisible) {
                activeSlug = firstVisible.dataset.value || '';
                setActive(table, 'period', activeSlug, firstVisible);
            }
        }

        return activeSlug;
    }

    function safeParseJSON(raw, fallback) {
        if (!raw) {
            return fallback;
        }
        try {
            const parsed = JSON.parse(raw);
            return parsed || fallback;
        } catch (err) {
            return fallback;
        }
    }

    // Mirror period filtering for location tabs.
    function filterLocationsByPlatform(table, platform) {
        const nav = table.querySelector('.pwpl-dimension-nav[data-dimension="location"]');
        if (!nav) {
            return '';
        }
        const availability = table.dataset.availability ? safeParseJSON(table.dataset.availability, {}) : {};
        const locationsByPlatform = availability.locationsByPlatform || {};
        const allowedLocations = platform && locationsByPlatform[platform] ? locationsByPlatform[platform] : [];
        const tabs = nav.querySelectorAll('.pwpl-tab');
        let activeSlug = table.dataset.activeLocation || '';
        tabs.forEach(function(tab){
            const slug = tab.dataset.value || '';
            const allowed = !allowedLocations.length || allowedLocations.indexOf(slug) !== -1;
            tab.classList.toggle('pwpl-hidden', !allowed);
            if (!allowed && tab.classList.contains('is-active')) {
                tab.classList.remove('is-active');
                tab.setAttribute('aria-pressed', 'false');
                activeSlug = '';
            }
        });

        if (!activeSlug) {
            const firstVisible = nav.querySelector('.pwpl-tab:not(.pwpl-hidden)');
            if (firstVisible) {
                activeSlug = firstVisible.dataset.value || '';
                setActive(table, 'location', activeSlug, firstVisible);
            }
        }

        return activeSlug;
    }
    
    function ensurePlatformDefault(table) {
        const allowed = parseAllowedPlatforms(table);
        if (!allowed.length) {
            table.querySelectorAll('.pwpl-plan.pwpl-hidden').forEach(function(plan){
                plan.classList.remove('pwpl-hidden');
            });
            return;
        }

        const nav = table.querySelector('.pwpl-dimension-nav[data-dimension="platform"]');
        let active = table.dataset.activePlatform || table.dataset.initialPlatform || allowed[0];
        if (allowed.indexOf(active) === -1) {
            active = allowed[0];
        }
        table.dataset.initialPlatform = allowed[0];
        table.dataset.activePlatform = active;

        if (nav) {
            const tabs = nav.querySelectorAll('.pwpl-tab');
            let matched = false;
            tabs.forEach(function(tab){
                const value = tab.dataset.value || '';
                if (value === active && !matched) {
                    tab.classList.add('is-active');
                    tab.setAttribute('aria-pressed', 'true');
                    matched = true;
                } else {
                    tab.classList.remove('is-active');
                    tab.setAttribute('aria-pressed', 'false');
                }
            });
            if (!matched && tabs.length) {
                const fallback = tabs[0];
                fallback.classList.add('is-active');
                fallback.setAttribute('aria-pressed', 'true');
                active = fallback.dataset.value || allowed[0];
                table.dataset.activePlatform = active;
            }
        }

        filterPlansByPlatform(table, active);
    }

    function parseJSONAttribute(element, attribute, fallback) {
        if (!element) {
            return fallback;
        }
        const cacheKey = '__pwpl' + attribute.charAt(0).toUpperCase() + attribute.slice(1);
        if (Object.prototype.hasOwnProperty.call(element, cacheKey)) {
            return element[cacheKey];
        }
        let value = fallback;
        const raw = element.getAttribute('data-' + attribute.replace(/[A-Z]/g, function(match){ return '-' + match.toLowerCase(); })) || '';
        if (raw) {
            try {
                value = JSON.parse(raw);
            } catch (err) {
                value = fallback;
            }
        }
        element[cacheKey] = value || fallback;
        return element[cacheKey];
    }

    function getTableBadges(table) {
        return parseJSONAttribute(table, 'badges', {});
    }

    function getDimensionLabels(table) {
        return parseJSONAttribute(table, 'dimensionLabels', {});
    }

    function getPlanBadges(plan) {
        return parseJSONAttribute(plan, 'badgesOverride', {});
    }

    function parseBadgeDate(dateStr, endOfDay) {
        if (!dateStr) {
            return null;
        }
        const parts = dateStr.split('-');
        if (parts.length !== 3) {
            return null;
        }
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        if (Number.isNaN(year) || Number.isNaN(month) || Number.isNaN(day)) {
            return null;
        }
        return new Date(year, month, day, endOfDay ? 23 : 0, endOfDay ? 59 : 0, endOfDay ? 59 : 0, endOfDay ? 999 : 0);
    }

    function badgeIsActive(badge) {
        if (!badge || typeof badge !== 'object') {
            return false;
        }
        const now = new Date();
        const start = parseBadgeDate(badge.start || '', false);
        if (start && start.getTime() > now.getTime()) {
            return false;
        }
        const end = parseBadgeDate(badge.end || '', true);
        if (end && end.getTime() < now.getTime()) {
            return false;
        }
        return true;
    }

    function hexToRgba(hex, alpha) {
        if (!hex) {
            return '';
        }
        var value = String(hex).trim();
        if (!value) {
            return '';
        }
        if (value[0] === '#') {
            value = value.slice(1);
        }
        if (value.length === 3) {
            value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
        }
        if (value.length !== 6 || /[^0-9a-f]/i.test(value)) {
            return '';
        }
        var intVal = parseInt(value, 16);
        var r = (intVal >> 16) & 255;
        var g = (intVal >> 8) & 255;
        var b = intVal & 255;
        var a = typeof alpha === 'number' ? Math.min(Math.max(alpha, 0), 1) : 0.35;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + a + ')';
    }

    function findBadgeForSlug(collection, slug) {
        if (!Array.isArray(collection)) {
            return null;
        }
        for (let index = 0; index < collection.length; index++) {
            const badge = collection[index];
            if (!badge || typeof badge !== 'object') {
                continue;
            }
            if (badge.slug !== slug) {
                continue;
            }
            if (!badgeIsActive(badge)) {
                continue;
            }
            return badge;
        }
        return null;
    }

    function resolveBadge(selection, override, tableBadges) {
        const dimensions = ['period', 'location', 'platform'];
        const overridePriority = Array.isArray(override.priority) ? override.priority.filter(function(item){
            return dimensions.indexOf(item) !== -1;
        }) : [];
        const tablePriority = Array.isArray(tableBadges.priority) ? tableBadges.priority.filter(function(item){
            return dimensions.indexOf(item) !== -1;
        }) : [];
        const combined = overridePriority.length ? overridePriority : tablePriority;
        const priority = [];
        combined.concat(dimensions).forEach(function(dimension){
            if (priority.indexOf(dimension) === -1) {
                priority.push(dimension);
            }
        });

        for (let i = 0; i < priority.length; i++) {
            const dimension = priority[i];
            const slug = selection[dimension];
            if (!slug) {
                continue;
            }
            const overrideBadge = findBadgeForSlug(override[dimension], slug);
            if (overrideBadge) {
                return overrideBadge;
            }
            const tableBadge = findBadgeForSlug(tableBadges[dimension], slug);
            if (tableBadge) {
                return tableBadge;
            }
        }
        return null;
    }

    function updateBadge(plan, selection, tableBadges) {
        const badgeEl = plan.querySelector('[data-pwpl-badge]');
        if (!badgeEl) {
            return;
        }

        const toneClasses = ['pwpl-plan__badge--tone-success', 'pwpl-plan__badge--tone-info', 'pwpl-plan__badge--tone-warning', 'pwpl-plan__badge--tone-danger', 'pwpl-plan__badge--tone-neutral'];
        toneClasses.forEach(function(cls){ badgeEl.classList.remove(cls); });

        const badge = resolveBadge(selection, getPlanBadges(plan), tableBadges);

        const iconEl = badgeEl.querySelector('[data-pwpl-badge-icon]');
        const labelEl = badgeEl.querySelector('[data-pwpl-badge-label]');

        if (!badge || (!badge.label && !badge.icon)) {
            badgeEl.hidden = true;
            badgeEl.dataset.badgeColor = '';
            badgeEl.dataset.badgeText = '';
            badgeEl.dataset.badgeTone = '';
            badgeEl.style.removeProperty('--pwpl-badge-bg');
            badgeEl.style.removeProperty('--pwpl-badge-color');
            if (iconEl) {
                iconEl.textContent = '';
                iconEl.hidden = true;
            }
            if (labelEl) {
                labelEl.textContent = '';
            }
            return;
        }

        badgeEl.hidden = false;
        const color = badge.color || '';
        const textColor = badge.text_color || '';
        const tone = badge.tone || '';

        badgeEl.dataset.badgeColor = color;
        badgeEl.dataset.badgeText = textColor;
        badgeEl.dataset.badgeTone = tone;

        if (color) {
            badgeEl.style.setProperty('--pwpl-badge-bg', color);
            const glow = hexToRgba(color, 0.4);
            if (glow) {
                badgeEl.style.setProperty('--pwpl-badge-shadow-color', glow);
            }
        } else {
            badgeEl.style.removeProperty('--pwpl-badge-bg');
            badgeEl.style.removeProperty('--pwpl-badge-shadow-color');
        }
        if (textColor) {
            badgeEl.style.setProperty('--pwpl-badge-color', textColor);
        } else {
            badgeEl.style.removeProperty('--pwpl-badge-color');
        }

        // Apply tone class only when no custom colors are set.
        if (!color && !textColor && tone) {
            badgeEl.classList.add('pwpl-plan__badge--tone-' + tone);
        }

        if (iconEl) {
            iconEl.textContent = badge.icon || '';
            iconEl.hidden = !badge.icon;
        }

        if (labelEl && badge.label) {
            labelEl.textContent = badge.label;
        }
    }

    function updateLocation(plan, selection, dimensionLabels) {
        const locationEl = plan.querySelector('[data-pwpl-location]');
        if (!locationEl) {
            return;
        }
        const slug = selection.location || '';
        const labels = dimensionLabels.location || {};
        if (!slug || !labels[slug]) {
            locationEl.hidden = true;
            locationEl.textContent = '';
        } else {
            locationEl.hidden = false;
            locationEl.textContent = labels[slug];
        }
    }

    function updateCta(plan, variant) {
        const ctaButton = plan.querySelector('[data-pwpl-cta-button]');
        const ctaLabel = plan.querySelector('[data-pwpl-cta-label]');
        const inlineContainer = plan.querySelector('.fvps-card__cta-inline');
        const inlineBtn = inlineContainer ? inlineContainer.querySelector('.fvps-button--inline') : null;
        const inlineSpan = inlineBtn ? inlineBtn.querySelector('span') : null;
        if (!ctaButton || !ctaLabel) {
            return;
        }

        // Unavailable state overrides normal CTA rules
        if (variant && variant.unavailable) {
            // Show disabled button with clear label and no link
            ctaButton.hidden = false;
            ctaButton.removeAttribute('href');
            ctaButton.setAttribute('aria-disabled', 'true');
            ctaButton.classList.add('is-disabled');
            ctaLabel.textContent = __('Unavailable');
            if (inlineBtn && inlineSpan) {
                inlineBtn.removeAttribute('href');
                inlineBtn.setAttribute('aria-disabled', 'true');
                inlineBtn.classList.add('is-disabled');
                inlineSpan.textContent = __('Unavailable');
                if (inlineContainer) { inlineContainer.hidden = false; }
            }
            plan.classList.add('pwpl-unavailable');
            return;
        }

        // Otherwise, normal CTA logic
        if (!variant || !variant.cta_label || !variant.cta_url) {
            ctaButton.hidden = true;
            ctaButton.removeAttribute('href');
            ctaButton.removeAttribute('target');
            ctaButton.removeAttribute('rel');
            ctaLabel.textContent = '';
            if (inlineContainer) { inlineContainer.hidden = true; }
            plan.classList.remove('pwpl-unavailable');
            return;
        }

        ctaButton.hidden = false;
        ctaButton.setAttribute('href', variant.cta_url);
        ctaLabel.textContent = variant.cta_label;
        ctaButton.removeAttribute('aria-disabled');
        ctaButton.classList.remove('is-disabled');
        if (inlineBtn && inlineSpan) {
            inlineBtn.setAttribute('href', variant.cta_url);
            inlineBtn.removeAttribute('aria-disabled');
            inlineBtn.classList.remove('is-disabled');
            inlineSpan.textContent = variant.cta_label;
            if (inlineContainer) { inlineContainer.hidden = false; }
        }
        plan.classList.remove('pwpl-unavailable');

        if (variant.target && (variant.target === '_blank' || variant.target === '_self')) {
            ctaButton.setAttribute('target', variant.target);
            if (variant.target === '_blank' && !variant.rel) {
                ctaButton.setAttribute('rel', 'noopener noreferrer');
            }
        } else {
            ctaButton.removeAttribute('target');
        }

        if (variant.rel) {
            ctaButton.setAttribute('rel', variant.rel);
        } else if (variant.target !== '_blank') {
            ctaButton.removeAttribute('rel');
        }
    }

    function updateBilling(plan, selection, dimensionLabels) {
        const billingEl = plan.querySelector('[data-pwpl-billing]');
        if (!billingEl) {
            return;
        }
        const periodSlug = selection.period || '';
        const labels = dimensionLabels.period || {};
        if (!periodSlug || !labels[periodSlug]) {
            billingEl.hidden = true;
            billingEl.textContent = '';
            return;
        }
        billingEl.hidden = false;
        const label = String(labels[periodSlug]);
        billingEl.textContent = __( 'Billed' ) + ' ' + label;
    }

    function updateTable(table) {
        const selection = currentSelection(table);
        const plans = table.querySelectorAll('.pwpl-plan');
        const tableBadges = getTableBadges(table);
        const dimensionLabels = getDimensionLabels(table);
        plans.forEach(function(plan){
            const variants = getVariants(plan);
            const best = resolveVariant(variants, selection);
            const priceEl = plan.querySelector('[data-pwpl-price]');
            if (priceEl) {
                priceEl.innerHTML = buildPriceHTML(best);
            }
            updateBadge(plan, selection, tableBadges);
            updateLocation(plan, selection, dimensionLabels);
            updateCta(plan, best);
            updateBilling(plan, selection, dimensionLabels);
        });
    }

    function setActive(table, dimension, value, button) {
        table.dataset['active' + dimension.charAt(0).toUpperCase() + dimension.slice(1)] = value;
        const nav = table.querySelector('.pwpl-dimension-nav[data-dimension="' + dimension + '"]');
        if (nav) {
            nav.querySelectorAll('.pwpl-tab').forEach(function(tab){
                tab.classList.remove('is-active');
                tab.setAttribute('aria-pressed', 'false');
            });
            if (button) {
                button.classList.add('is-active');
                button.setAttribute('aria-pressed', 'true');
            }
        }
        if (dimension === 'platform') {
            filterPlansByPlatform(table, value);
            filterPeriodsByPlatform(table, value);
            filterLocationsByPlatform(table, value);
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
        ensurePlatformDefault(table);
        if (table.dataset.activePlatform) {
            const normalizedPeriod = filterPeriodsByPlatform(table, table.dataset.activePlatform);
            const normalizedLocation = filterLocationsByPlatform(table, table.dataset.activePlatform);
            if (normalizedPeriod) {
                table.dataset.activePeriod = normalizedPeriod;
            }
            if (normalizedLocation) {
                table.dataset.activeLocation = normalizedLocation;
            }
        }
        enhanceRail(table);
        updateTable(table);
    });

    function enhanceRail(table) {
        const rail = table.querySelector('.pwpl-plan-grid');
        if (!rail) {
            return;
        }

        const prevNav = table.querySelector('.pwpl-plan-nav--prev');
        const nextNav = table.querySelector('.pwpl-plan-nav--next');

        function updateNavVisibility() {
            const scrollLeft = rail.scrollLeft;
            const maxScroll = Math.max(rail.scrollWidth - rail.clientWidth, 0);
            const tolerance = 1;
            if (prevNav) {
                prevNav.hidden = scrollLeft <= tolerance;
            }
            if (nextNav) {
                nextNav.hidden = scrollLeft >= (maxScroll - tolerance);
            }
        }

        function scrollByDirection(direction) {
            const amount = rail.clientWidth * 0.8;
            const target = direction === 'next' ? rail.scrollLeft + amount : rail.scrollLeft - amount;
            rail.scrollTo({ left: target, behavior: 'smooth' });
        }

        if (prevNav) {
            prevNav.querySelector('button').addEventListener('click', function(){
                scrollByDirection('prev');
            });
        }
        if (nextNav) {
            nextNav.querySelector('button').addEventListener('click', function(){
                scrollByDirection('next');
            });
        }

        rail.addEventListener('scroll', updateNavVisibility, { passive: true });
        window.addEventListener('resize', updateNavVisibility);

        // Initial visibility
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(updateNavVisibility);
        } else {
            setTimeout(updateNavVisibility, 0);
        }
    }
})();

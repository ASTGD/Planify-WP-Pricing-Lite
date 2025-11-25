(function($){
  const doc = $(document);
  let overlay = $('#pwpl-plan-drawer-overlay');
  let drawer = $('#pwpl-plan-drawer');
  let modalDrawer = drawer;
  let modalOverlay = overlay;
  const inlineDrawer = $('#pwpl-plan-drawer-inline');
  const inlinePlaceholder = $('#pwpl-plan-drawer-panel .pwpl-drawer-placeholder');
  let currentMode = 'modal';
  let resizeTimer;
  const filters = {
    platform: new Set(),
    period: new Set(),
    location: new Set(),
  };
  if (!overlay.length) {
    overlay = $('<div class="pwpl-drawer__overlay" id="pwpl-plan-drawer-overlay" hidden></div>').appendTo('body');
  }
  if (!drawer.length) {
    drawer = $('<div class="pwpl-drawer" id="pwpl-plan-drawer" hidden></div>').appendTo('body');
  }
  modalDrawer = drawer;
  modalOverlay = overlay;

  let focusTrapHandler = null;
  const saleLabel = PWPL_Plans?.i18n?.sale || 'Sale';
  const unavailableLabel = PWPL_Plans?.i18n?.unavailable || 'Unavailable';

  const isInlineMode = () => inlineDrawer.length && window.innerWidth >= 1024;

  function selectPlanRow(row) {
    if (!row || !row.length) return;
    if (row.hasClass('is-loading')) return;
    const planId = row.data('planId');
    const tableId = row.data('tableId');
    if (!planId || !tableId) return;
    const alreadyActive = row.hasClass('is-active');
    if (alreadyActive && currentMode === 'inline' && inlineDrawer.attr('hidden') === 'false') {
      return;
    }
    row.addClass('is-active is-loading').siblings('.pwpl-plan-row').removeClass('is-active is-loading');
    loadDrawer(planId, tableId);
  }

  function openDrawer(html) {
    if (isInlineMode()) {
      currentMode = 'inline';
      drawer = inlineDrawer;
      overlay = modalOverlay;
      modalOverlay.attr('hidden', true);
      inlinePlaceholder.attr('hidden', true);
      drawer.html(html);
      drawer.removeAttr('role aria-modal');
      drawer.attr('hidden', false).attr('aria-hidden', 'false').addClass('pwpl-drawer--inline');
      $('body').removeClass('pwpl-modal-open');
      $(document).off('keydown.pwplTrap');
      focusTrapHandler = null;
    } else {
      currentMode = 'modal';
      drawer = modalDrawer;
      overlay = modalOverlay;
      drawer.removeClass('pwpl-drawer--inline');
      drawer.html(html);
      drawer.attr('hidden', false);
      overlay.attr('hidden', false);
      drawer.attr('aria-hidden', 'false');
      overlay.attr('aria-hidden', 'false');
      drawer.attr('role', 'dialog').attr('aria-modal', 'true');
      $('body').addClass('pwpl-modal-open');
      // focus the first input if present
      let focusables = drawer.find('input, select, textarea, button, a[href], [tabindex]').filter(':visible:not([tabindex="-1"])');
      let firstFocusable = focusables.first();
      let lastFocusable = focusables.last();
      if (firstFocusable.length) firstFocusable.trigger('focus');
      // basic focus trap
      focusTrapHandler = function(e){
        if (e.key !== 'Tab') return;
        focusables = drawer.find('input, select, textarea, button, a[href], [tabindex]').filter(':visible:not([tabindex="-1"])');
        firstFocusable = focusables.first();
        lastFocusable = focusables.last();
        if (!focusables.length) return;
        if (!focusables.length) return;
        if (e.shiftKey) {
          if (document.activeElement === firstFocusable[0]) {
            e.preventDefault();
            lastFocusable.trigger('focus');
          }
        } else {
          if (document.activeElement === lastFocusable[0]) {
            e.preventDefault();
            firstFocusable.trigger('focus');
          }
        }
      };
      $(document).on('keydown.pwplTrap', focusTrapHandler);
    }
  }

  function setActiveVariant(index) {
    const navItems = drawer.find('.pwpl-variant-nav-item').not('.is-filtered-out');
    const cards = drawer.find('[data-variant-card]').not('.is-filtered-out');
    const nav = navItems.filter('[data-variant-index="' + index + '"]');
    const card = cards.filter('[data-variant-index="' + index + '"]');
    navItems.removeClass('is-active').attr('aria-selected', 'false');
    cards.removeClass('is-active');
    if (nav.length) {
      nav.addClass('is-active').attr('aria-selected', 'true');
    }
    if (card.length) {
      card.addClass('is-active').removeClass('is-collapsed');
      card.find('.pwpl-variant-summary').attr('aria-expanded', 'true');
      const body = drawer.find('.pwpl-drawer__body');
      if (body.length) {
        const bodyEl = body.get(0);
        const cardEl = card.get(0);
        const bodyRect = bodyEl.getBoundingClientRect();
        const cardRect = cardEl.getBoundingClientRect();
        const offsetTop = cardRect.top - bodyRect.top + bodyEl.scrollTop - 40;
        body.scrollTop(offsetTop);
      }
    }
    updateVariantsEmptyState();
  }

  function updateVariantsEmptyState() {
    const navEmpty = drawer.find('[data-variants-nav-empty]');
    const detailEmpty = drawer.find('[data-variants-empty]');
    const navItems = drawer.find('.pwpl-variant-nav-item').not('.is-filtered-out');
    const variantsWrap = drawer.find('.pwpl-variants');
    if (navItems.length) {
      navEmpty.attr('hidden', true);
      detailEmpty.attr('hidden', true);
      variantsWrap.attr('aria-hidden', 'false');
    } else {
      navEmpty.removeAttr('hidden');
      detailEmpty.removeAttr('hidden');
      variantsWrap.attr('aria-hidden', 'false');
    }
  }

  function initVariantNavigator() {
    const navItems = drawer.find('.pwpl-variant-nav-item');
    const active = navItems.filter('.is-active').first();
    if (active.length) {
      setActiveVariant(active.data('variantIndex'));
    } else if (navItems.length) {
      setActiveVariant(navItems.first().data('variantIndex'));
    } else {
      updateVariantsEmptyState();
    }
  }

  function matchesFilters(navItem) {
    const platform = navItem.attr('data-platform') || '';
    const period = navItem.attr('data-period') || '';
    const location = navItem.attr('data-location') || '';
    const matchDim = (dim, value) => {
      if (!filters[dim] || filters[dim].size === 0) return true;
      if (!value) return false;
      return filters[dim].has(value);
    };
    return matchDim('platform', platform) && matchDim('period', period) && matchDim('location', location);
  }

  function applyFilters() {
    const navItems = drawer.find('.pwpl-variant-nav-item');
    const cards = drawer.find('[data-variant-card]');
    navItems.each(function(){
      const item = $(this);
      const match = matchesFilters(item);
      item.toggleClass('is-filtered-out', !match);
    });
    cards.each(function(){
      const card = $(this);
      const idx = card.data('variantIndex');
      const nav = navItems.filter('[data-variant-index="' + idx + '"]');
      const match = nav.length ? !nav.hasClass('is-filtered-out') : true;
      card.toggleClass('is-filtered-out', !match);
    });

    const visibleNav = navItems.not('.is-filtered-out');
    if (visibleNav.length) {
      const activeVisible = visibleNav.filter('.is-active');
      if (!activeVisible.length) {
        setActiveVariant(visibleNav.first().data('variantIndex'));
      }
    } else {
      drawer.find('[data-variant-card]').removeClass('is-active');
    }
    updateVariantsEmptyState();
  }

  function buildNavItem(index) {
    const tpl = $('#pwpl-tpl-variants-nav');
    if (!tpl.length) return null;
    return $(tpl.html().replace(/__INDEX__/g, index));
  }

  function syncNavSummary(index) {
    const card = drawer.find('[data-variant-card][data-variant-index="' + index + '"]');
    const nav = drawer.find('.pwpl-variant-nav-item[data-variant-index="' + index + '"]');
    if (!card.length || !nav.length) return;
    const navMeta = nav.find('.pwpl-variant-nav-item__meta');
    const platformOpt = card.find('select[name*="[platform]"] option:selected');
    const periodOpt = card.find('select[name*="[period]"] option:selected');
    const locationOpt = card.find('select[name*="[location]"] option:selected');
    const platform = platformOpt.text().trim() || 'Any platform';
    const period = periodOpt.text().trim() || 'Any period';
    const location = locationOpt.text().trim() || 'Any location';
    const price = (card.find('input[name*="[price]"]').first().val() || '').toString().trim();
    const sale = (card.find('input[name*="[sale_price]"]').first().val() || '').toString().trim();
    const unavailable = card.find('input[name*="[unavailable]"]').is(':checked');
    const hasSale = sale !== '';
    const platformSlug = platformOpt.val() || '';
    const periodSlug = periodOpt.val() || '';
    const locationSlug = locationOpt.val() || '';

    const summaryPrice = price || 'No price';
    nav.find('.pwpl-variant-nav-item__labels .pwpl-chip').eq(0).text(platform || 'Any platform');
    nav.find('.pwpl-variant-nav-item__labels .pwpl-chip').eq(1).text(period || 'Any period');
    nav.find('.pwpl-variant-nav-item__labels .pwpl-chip').eq(2).text(location || 'Any location');
    nav.find('.pwpl-variant-nav-item__price').text(summaryPrice);
    nav.attr('data-platform', platformSlug);
    nav.attr('data-period', periodSlug);
    nav.attr('data-location', locationSlug);

    let saleEl = nav.find('.pwpl-variant-nav-item__sale');
    if (hasSale) {
      if (!saleEl.length) {
        saleEl = $('<span class="pwpl-variant-nav-item__sale"></span>').appendTo(navMeta);
      }
      saleEl.text('Sale ' + sale);
    } else {
      saleEl.remove();
    }

    navMeta.find('.pwpl-chip--accent').remove();
    navMeta.find('.pwpl-chip--warning').remove();
    if (hasSale) {
      navMeta.append('<span class="pwpl-chip pwpl-chip--accent">' + saleLabel + '</span>');
    }
    if (unavailable) {
      navMeta.append('<span class="pwpl-chip pwpl-chip--warning">' + unavailableLabel + '</span>');
    }
  }

  function closeDrawer() {
    if (currentMode === 'inline') {
      inlineDrawer.attr('hidden', true).attr('aria-hidden', 'true').empty();
      inlinePlaceholder.removeAttr('hidden');
      drawer = inlineDrawer;
    } else {
      modalDrawer.attr('hidden', true).empty();
      modalOverlay.attr('hidden', true);
      modalDrawer.attr('aria-hidden', 'true');
      modalOverlay.attr('aria-hidden', 'true');
      modalDrawer.removeAttr('role aria-modal');
      $('body').removeClass('pwpl-modal-open');
      $(document).off('keydown.pwplTrap');
      focusTrapHandler = null;
      drawer = modalDrawer;
      overlay = modalOverlay;
    }
    $('.pwpl-plan-row').removeClass('is-active is-loading');
  }

  function loadDrawer(planId, tableId) {
    const inline = isInlineMode();
    drawer = inline && inlineDrawer.length ? inlineDrawer : modalDrawer;
    overlay = modalOverlay;
    if (inline && inlinePlaceholder.length) {
      inlinePlaceholder.attr('hidden', true);
    }
    drawer.html('<div class="pwpl-drawer__loading">' + (PWPL_Plans?.i18n?.loading || 'Loading…') + '</div>');
    drawer.attr('hidden', false);
    if (!inline) {
      overlay.attr('hidden', false);
    }
    $.get(PWPL_Plans.ajaxUrl, {
      action: 'pwpl_render_plan_drawer',
      plan_id: planId,
      table_id: tableId,
      nonce: PWPL_Plans.nonce,
    }).done(function(resp){
      if (resp && resp.success && resp.data && resp.data.html) {
        openDrawer(resp.data.html);
        initVariantNavigator();
        applyFilters();
      } else {
        drawer.html('<div class="pwpl-drawer__error">' + (PWPL_Plans?.i18n?.error || 'Unable to load the plan.') + '</div>');
      }
    }).fail(function(){
      drawer.html('<div class="pwpl-drawer__error">' + (PWPL_Plans?.i18n?.error || 'Unable to load the plan.') + '</div>');
    }).always(function(){
      $('.pwpl-plan-row').removeClass('is-loading');
    });
  }

  // Handle open: plan row click
  doc.on('click', '.pwpl-plan-row', function(e){
    if ($(e.target).closest('a').length) {
      return;
    }
    e.preventDefault();
    selectPlanRow($(this));
  });

  doc.on('keydown', '.pwpl-plan-row', function(e){
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      selectPlanRow($(this));
    }
  });

  // Close events
  doc.on('click', '.pwpl-drawer__close', function(e){
    e.preventDefault();
    closeDrawer();
  });
  overlay.on('click', function(){
    closeDrawer();
  });
  $(window).on('keydown', function(e){
    if (e.key === 'Escape') {
      closeDrawer();
    }
  });

  // Repeatable rows inside drawer (simplified helper)
  doc.on('click', '.pwpl-add-row', function(e){
    e.preventDefault();
    const target = $(this).data('target');
    if (!target) return;
    const container = drawer.find('[data-target="' + target + '"]');
    if (!container.length) return;
    const nextIndex = parseInt(container.data('next-index'), 10) || 0;
    const tpl = $('#pwpl-tpl-' + target);
    if (!tpl.length) return;
    let html = tpl.html().replace(/__INDEX__/g, nextIndex);
    container.data('next-index', nextIndex + 1);
    if (target === 'variants') {
      const actions = container.find('.pwpl-variants__actions');
      if (actions.length) {
        $(html).insertBefore(actions);
      } else {
        container.append(html);
      }
      const navContainer = drawer.find('.pwpl-variants-nav');
      const navItem = buildNavItem(nextIndex);
      if (navItem && navContainer.length) {
        navContainer.append(navItem);
      }
      updateVariantsEmptyState();
      applyFilters();
      setActiveVariant(nextIndex);
    } else if (target === 'specs') {
      const actions = container.find('.pwpl-specs__actions');
      if (actions.length) {
        $(html).insertBefore(actions);
      } else {
        container.append(html);
      }
    } else {
      container.append(html);
    }
  });

  doc.on('click', '.pwpl-remove-row', function(e){
    e.preventDefault();
    const variantCard = $(this).closest('.pwpl-variant-card');
    if (variantCard.length) {
      const idx = variantCard.data('variantIndex');
      variantCard.remove();
      drawer.find('.pwpl-variant-nav-item[data-variant-index="' + idx + '"]').remove();
      const remaining = drawer.find('.pwpl-variant-nav-item');
      updateVariantsEmptyState();
      applyFilters();
      if (remaining.length) {
        const next = remaining.filter(function(){ return $(this).data('variantIndex') > idx; }).first();
        const targetIdx = next.length ? next.data('variantIndex') : remaining.last().data('variantIndex');
        setActiveVariant(targetIdx);
      }
      return;
    }
    const row = $(this).closest('.pwpl-spec-row');
    if (row.length) {
      row.remove();
    }
  });

  doc.on('click', '.pwpl-variant-nav-item', function(e){
    e.preventDefault();
    const idx = $(this).data('variantIndex');
    setActiveVariant(idx);
    drawer.find('.pwpl-variants').scrollTop(0);
  });

  doc.on('click', '.pwpl-filter-toggle', function(e){
    e.preventDefault();
    const menu = $(this).closest('.pwpl-filter-menu');
    const list = menu.find('.pwpl-filter-menu__list');
    const expanded = menu.hasClass('is-open');
    $('.pwpl-filter-menu').removeClass('is-open');
    $('.pwpl-filter-menu__list').attr('hidden', true);
    $('.pwpl-filter-toggle').attr('aria-expanded', 'false');
    if (!expanded) {
      menu.addClass('is-open');
      list.removeAttr('hidden');
      $(this).attr('aria-expanded', 'true');
    }
  });

  doc.on('click', '.pwpl-advanced-toggle__btn', function(e){
    e.preventDefault();
    const content = drawer.find('.pwpl-advanced-content');
    const expanded = $(this).attr('aria-expanded') === 'true';
    if (expanded) {
      $(this).attr('aria-expanded', 'false').text(PWPL_Plans?.i18n?.advancedShow || 'Show advanced');
      content.attr('hidden', true);
    } else {
      $(this).attr('aria-expanded', 'true').text(PWPL_Plans?.i18n?.advancedHide || 'Hide advanced');
      content.removeAttr('hidden');
    }
  });

  doc.on('change', '.pwpl-filter-checkbox', function(){
    const dim = $(this).data('filterDim');
    const value = $(this).data('filterValue');
    if (!dim) return;
    const allBox = $(this).closest('.pwpl-filter-menu__list').find('.pwpl-filter-checkbox[data-filter-value=""]');
    if (value === '') {
      if ($(this).is(':checked')) {
        filters[dim].clear();
        allBox.prop('checked', true);
        $(this).closest('.pwpl-filter-menu__list').find('.pwpl-filter-checkbox').not(this).prop('checked', false);
      } else {
        allBox.prop('checked', true);
      }
    } else {
      allBox.prop('checked', false);
      if ($(this).is(':checked')) {
        filters[dim].add(value);
      } else {
        filters[dim].delete(value);
      }
      if (filters[dim].size === 0) {
        allBox.prop('checked', true);
      }
    }
    updateFilterLabels();
    applyFilters();
  });

  function updateFilterLabels() {
    drawer.find('.pwpl-filter-menu').each(function(){
      const dim = $(this).data('filterDim');
      const toggle = $(this).find('.pwpl-filter-toggle');
      const count = filters[dim] ? filters[dim].size : 0;
      if (!count) {
        toggle.text('All');
      } else {
        toggle.text(count + ' selected');
      }
    });
  }

  doc.on('click', function(e){
    if (!$(e.target).closest('.pwpl-filter-menu').length) {
      $('.pwpl-filter-menu__list').attr('hidden', true);
      $('.pwpl-filter-menu').removeClass('is-open');
      $('.pwpl-filter-toggle').attr('aria-expanded', 'false');
    }
  });

  doc.on('input change', '[data-variant-card] input, [data-variant-card] select', function(){
    const card = $(this).closest('[data-variant-card]');
    if (!card.length) return;
    syncNavSummary(card.data('variantIndex'));
    applyFilters();
  });

  // Variant collapse/expand
  doc.on('click', '.pwpl-variant-summary', function(e){
    e.preventDefault();
    const card = $(this).closest('[data-variant-card]');
    const details = card.find('[data-variant-details]');
    const expanded = $(this).attr('aria-expanded') === 'true';
    $(this).attr('aria-expanded', expanded ? 'false' : 'true');
    card.toggleClass('is-collapsed', expanded);
    details.toggle(!expanded);
  });

  $(window).on('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function(){
      if (isInlineMode()) {
        const activeRow = $('.pwpl-plan-row.is-active').first();
        if (inlineDrawer.attr('hidden') === 'true' && activeRow.length) {
          selectPlanRow(activeRow);
        }
      }
    }, 200);
  });

})(jQuery);

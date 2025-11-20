(function($){
  const doc = $(document);
  let overlay = $('#pwpl-plan-drawer-overlay');
  let drawer = $('#pwpl-plan-drawer');
  if (!overlay.length) {
    overlay = $('<div class="pwpl-drawer__overlay" id="pwpl-plan-drawer-overlay" hidden></div>').appendTo('body');
  }
  if (!drawer.length) {
    drawer = $('<div class="pwpl-drawer" id="pwpl-plan-drawer" hidden></div>').appendTo('body');
  }

  function openDrawer(html) {
    drawer.html(html);
    drawer.attr('hidden', false);
    overlay.attr('hidden', false);
    drawer.attr('aria-hidden', 'false');
    overlay.attr('aria-hidden', 'false');
    // focus the first input if present
    const focusable = drawer.find('input, select, textarea, button').filter(':visible').first();
    if (focusable.length) {
      focusable.trigger('focus');
    }
  }

  function closeDrawer() {
    drawer.attr('hidden', true).empty();
    overlay.attr('hidden', true);
    drawer.attr('aria-hidden', 'true');
    overlay.attr('aria-hidden', 'true');
  }

  function loadDrawer(planId, tableId) {
    drawer.html('<div class="pwpl-drawer__loading">' + (PWPL_Plans?.i18n?.loading || 'Loading…') + '</div>');
    drawer.attr('hidden', false);
    overlay.attr('hidden', false);
    $.get(PWPL_Plans.ajaxUrl, {
      action: 'pwpl_render_plan_drawer',
      plan_id: planId,
      table_id: tableId,
      nonce: PWPL_Plans.nonce,
    }).done(function(resp){
      if (resp && resp.success && resp.data && resp.data.html) {
        openDrawer(resp.data.html);
      } else {
        drawer.html('<div class="pwpl-drawer__error">' + (PWPL_Plans?.i18n?.error || 'Unable to load the plan.') + '</div>');
      }
    }).fail(function(){
      drawer.html('<div class="pwpl-drawer__error">' + (PWPL_Plans?.i18n?.error || 'Unable to load the plan.') + '</div>');
    });
  }

  // Handle open: plan card or specific edit button
  doc.on('click', '.pwpl-plan-card, .pwpl-plan-card__edit', function(e){
    // avoid triggering when clicking links/buttons inside the card except our edit class
    if ($(e.target).closest('a, button').length && !$(e.target).hasClass('pwpl-plan-card__edit')) {
      return;
    }
    const card = $(this).closest('.pwpl-plan-card');
    const planId = card.data('planId');
    const tableId = card.data('tableId');
    if (!planId || !tableId) return;
    e.preventDefault();
    loadDrawer(planId, tableId);
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
    container.append(html);
  });

  doc.on('click', '.pwpl-remove-row', function(e){
    e.preventDefault();
    const row = $(this).closest('.pwpl-spec-row, .pwpl-variant-card');
    if (row.length) {
      row.remove();
    }
  });
})(jQuery);

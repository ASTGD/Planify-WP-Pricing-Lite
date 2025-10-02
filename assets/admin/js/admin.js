// Planify WP Pricing Lite — Admin script
(function($){
  $(function(){
    var $dimensions = $('[data-pwpl-dimensions]');

    $dimensions.on('change', '.pwpl-dimension input[type="checkbox"]', function(){
      var $wrapper = $(this).closest('.pwpl-dimension');
      $wrapper.find('.pwpl-dimension-options')[ this.checked ? 'slideDown' : 'slideUp' ](150);
    });

    var templateCache = {};

    function getTemplate(name) {
      if (!name) {
        return null;
      }
      if (templateCache[name]) {
        return templateCache[name];
      }
      if (typeof wp !== 'undefined' && wp.template) {
        try {
          templateCache[name] = wp.template(name);
          return templateCache[name];
        } catch (err) {
          templateCache[name] = null;
        }
      }
      return templateCache[name] || null;
    }

    $(document).on('click', '.pwpl-add-row', function(){
      var target = $(this).data('target');
      var $table = $('.pwpl-repeatable[data-pwpl-repeatable="' + target + '"]');
      if (!$table.length) {
        return;
      }
      var templateName = $table.data('template') || target;
      var template = getTemplate(templateName);
      if (!template) {
        return;
      }
      var nextIndex = parseInt($table.data('next-index'), 10) || 0;
      $table.data('next-index', nextIndex + 1);
      var html = template({ index: nextIndex });
      $table.find('tbody').append(html);
    });

    $(document).on('click', '.pwpl-remove-row', function(){
      var $row = $(this).closest('tr');
      var $table = $row.closest('.pwpl-repeatable');
      $row.remove();
      if ( !$table.find('tbody tr').length ) {
        // Ensure at least one blank row remains
        var target = $table.data('pwpl-repeatable');
        var templateName = $table.data('template') || target;
        var template = getTemplate(templateName);
        if ( template ) {
          var nextIndex = parseInt($table.data('next-index'), 10) || 0;
          $table.data('next-index', nextIndex + 1);
          var html = template({ index: nextIndex });
          $table.find('tbody').append(html);
        }
      }
    });

    function showCopyFeedback($feedback, success) {
      if (!$feedback || !$feedback.length) {
        return;
      }
      var messages = window.PWPL_Admin || {};
      var message = success ? (messages.copySuccess || 'Copied!') : (messages.copyError || 'Copy failed. Copy manually.');
      $feedback
        .text(message)
        .removeClass('is-error')
        .toggleClass('is-error', !success)
        .addClass('is-visible');

      clearTimeout($feedback.data('pwplTimeout'));
      var timeout = setTimeout(function(){
        $feedback.removeClass('is-visible is-error').text('');
      }, 2000);
      $feedback.data('pwplTimeout', timeout);
    }

    $(document).on('click', '.pwpl-copy-shortcode', function(e){
      e.preventDefault();
      var target = $(this).data('target');
      var $input = $('#' + target);
      if ( !$input.length ) {
        return;
      }
      var text = $input.val();
      var $feedback = $(this).closest('.pwpl-shortcode-field').siblings('[data-pwpl-feedback]').first();

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){
          showCopyFeedback($feedback, true);
        }).catch(function(){
          fallback();
        });
      } else {
        fallback();
      }

      function fallback(){
        var inputEl = $input.get(0);
        var selection = document.getSelection();
        var originalRange = selection && selection.rangeCount ? selection.getRangeAt(0) : null;

        inputEl.focus();
        inputEl.select();

        var successful = false;
        try {
          successful = document.execCommand('copy');
        } catch (err) {
          successful = false;
        }

        if (originalRange && selection) {
          selection.removeAllRanges();
          selection.addRange(originalRange);
        } else {
          inputEl.blur();
        }

        showCopyFeedback($feedback, successful);
      }
    });

    function togglePlanBadgeFields() {
      var $toggle = $('#pwpl_plan_badges_override_enabled');
      if (!$toggle.length) {
        return;
      }
      var $fields = $('[data-pwpl-plan-badge-fields]');
      if (!$fields.length) {
        return;
      }
      if ($toggle.is(':checked')) {
        $fields.slideDown(150);
      } else {
        $fields.slideUp(150);
      }
    }

    togglePlanBadgeFields();
    $(document).on('change', '#pwpl_plan_badges_override_enabled', togglePlanBadgeFields);

    function updateRangeDisplay($input) {
      var selector = $input.data('pwplRangeOutput');
      if (!selector) {
        return;
      }
      var unit = $input.data('pwplRangeUnit') || '';
      var value = $input.val();
      var $output = $(selector);
      if ($output.length) {
        $output.text(value + unit);
      }
    }

    $('[data-pwpl-range]').each(function(){
      updateRangeDisplay($(this));
    });

    $(document).on('input change', '[data-pwpl-range]', function(){
      updateRangeDisplay($(this));
    });
  });
})(jQuery);

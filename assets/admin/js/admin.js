// Planify WP Pricing Lite — Admin script
(function($){
  $(function(){
    var $dimensions = $('[data-pwpl-dimensions]');

    $dimensions.on('change', '.pwpl-dimension input[type="checkbox"]', function(){
      var $wrapper = $(this).closest('.pwpl-dimension');
      $wrapper.find('.pwpl-dimension-options')[ this.checked ? 'slideDown' : 'slideUp' ](150);
    });

    var templates = {
      specs: typeof wp !== 'undefined' && wp.template ? wp.template('pwpl-row-specs') : null,
      variants: typeof wp !== 'undefined' && wp.template ? wp.template('pwpl-row-variants') : null
    };

    $(document).on('click', '.pwpl-add-row', function(){
      var target = $(this).data('target');
      var $table = $('.pwpl-repeatable[data-pwpl-repeatable="' + target + '"]');
      if (!$table.length || !templates[target]) {
        return;
      }
      var nextIndex = parseInt($table.data('next-index'), 10) || 0;
      $table.data('next-index', nextIndex + 1);
      var html = templates[target]({ index: nextIndex });
      $table.find('tbody').append(html);
    });

    $(document).on('click', '.pwpl-remove-row', function(){
      var $row = $(this).closest('tr');
      var $table = $row.closest('.pwpl-repeatable');
      $row.remove();
      if ( !$table.find('tbody tr').length ) {
        // Ensure at least one blank row remains
        var target = $table.data('pwpl-repeatable');
        if ( templates[target] ) {
          var nextIndex = parseInt($table.data('next-index'), 10) || 0;
          $table.data('next-index', nextIndex + 1);
          var html = templates[target]({ index: nextIndex });
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
  });
})(jQuery);

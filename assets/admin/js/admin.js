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
  });
})(jQuery);


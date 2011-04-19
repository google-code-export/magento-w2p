(function ($) {
  $.fn.text_field_resizer = function () {
    return this.each(function () {
      var $field = $(this);

      $field.resizable({
        handles: $field.attr('tagName').toUpperCase() == 'TEXTAREA'
                   ? 'se, sw' : 'e, w',

        create: function () {
          $field.parent().find('.ui-resizable-handle').mousedown(function () {
            $field.focus();
          });

          $field.data('text-field-resizer',
                  { 'field-css': $field.attr('style'),
                    'wrapper-css': $field.parent().attr('style') }
          );

          $field.blur(function () {
            var data = $field.data('text-field-resizer')

            $field
              .attr('style', data['field-css'])
              .parent().attr('style', data['wrapper-css']);
          })
        },

        start: function () {
          $field.parent().css('z-index', 1000);
        }
      });
    });
  }
})(jQuery);


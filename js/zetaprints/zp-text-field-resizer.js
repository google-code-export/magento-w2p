(function ($) {

  function restore_field_style (event) {
    var $field = $(this);
    var data = $field.data('text-field-resizer')

    $field
      .unbind(event)
      .attr('style', data['field-css'])
      .parent().attr('style', data['wrapper-css'])
  }

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
        },

        start: function () {
          $field.parent().css('z-index', 1000);
        },

        stop: function () {
          $field.bind('blur', restore_field_style);
        }
      });
    });
  }
})(jQuery);


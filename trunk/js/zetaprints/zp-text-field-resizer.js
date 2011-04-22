(function ($) {

  function restore_field_style (event) {
    var $field = $(this);
    var data = $field.data('text-field-resizer')

    $field.unbind(event);

    if (data['style'] == undefined)
      $field.parent().removeAttr('style');
    else
      $field.parent().attr('style', data['style']);
  }

  $.fn.text_field_resizer = function () {
    return this.each(function () {
      var $wrapper = $(this);
      var $field = $wrapper.find('.input-text, textarea');

      $wrapper.resizable({
        handles: $field.attr('tagName').toUpperCase() == 'TEXTAREA'
                   ? 'se, sw' : 'e, w',

        create: function () {
          $wrapper.mousedown(function () {
            $field.focus();
          });

          $field.data('text-field-resizer',
                      { 'style': $wrapper.attr('style') } );
        },

        start: function () {
          $wrapper.css('z-index', 1000);
          $field.focus();
        },

        stop: function () {
          $field.focus().bind('blur', restore_field_style);
        }
      });
    });
  }
})(jQuery);


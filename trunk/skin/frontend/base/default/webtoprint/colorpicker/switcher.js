;jQuery(function($) {
  $.colorpicker.parts.switcher = function (inst) {
    this.init = function () {
      var $dialog = inst.dialog,
          $rgb = $('<div class="ui-colorpicker-switcher-rgb">RGB</div>'),
          $cmyk = $('<div class="ui-colorpicker-switcher-cmyk">CMYK</div>');

      inst.element.val(inst._formatColor('#HEX', inst.color));

      $dialog.addClass('ui-colorpicker-mode-rgb');

      $('<div class="ui-colorpicker-switcher"/>')
        .append($rgb, $cmyk)
        .appendTo($dialog);

      $($rgb).on('click', function () {
        if ($dialog.hasClass('ui-colorpicker-mode-rgb'))
          return;

        inst.options.colorFormat = '#HEX';
        inst.element.val(inst._formatColor('#HEX', inst.color));

        $dialog
          .removeClass('ui-colorpicker-mode-cmyk')
          .addClass('ui-colorpicker-mode-rgb');
      });

      $($cmyk).on('click', function () {
        if ($dialog.hasClass('ui-colorpicker-mode-cmyk'))
          return;

        inst.options.colorFormat = '#CMYK';
        inst.element.val(inst._formatColor('#CMYK', inst.color));

        inst.mode = 'h';
        inst._updateAllParts();

        $dialog
          .removeClass('ui-colorpicker-mode-rgb')
          .addClass('ui-colorpicker-mode-cmyk');
      });
    };
  };

  $.colorpicker.writers['#CMYK'] = function (color, that) {
    var cmyk = color.getCMYK(),
        c = ('00' + Math.floor(cmyk.c * 255)).slice(-3),
        m = ('00' + Math.floor(cmyk.m * 255)).slice(-3),
        y = ('00' + Math.floor(cmyk.y * 255)).slice(-3),
        k = ('00' + Math.floor(cmyk.k * 255)).slice(-3);

    return '#' + c + m + y + k;
  }

  $.colorpicker.parsers['#CMYK'] = function (color) {
    var m = /^#(\d{3})(\d{3})(\d{3})(\d{3})$/.exec(color);

    if (m)
      return (new $.colorpicker.Color()).setCMYK(
        m[1] / 255,
        m[2] / 255,
        m[3] / 255,
        m[4] / 255
      );
  }
});
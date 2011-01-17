(function ($) {
  var methods = {
    hide : function () {
      $editor = this.data('text-field-editor');

      if ($editor)
        $editor.removeClass('opened');
    }
  };

  $.fn.text_field_editor = function (method) {
    var settings = {
      button_parent: null,
      change: function (data) {}
    };

    if (methods[method])
      return methods[method]
               .apply(this, Array.prototype.slice.call(arguments, 1));
    else if (typeof method === 'object' || ! method)
      $.extend(settings, method);
    else
      $.error('Method ' +  method +
              ' does not exist on jQuery.text_field_editor');

    var $field = this;

    var $editor = $('<div class="zp-text-field-editor" />')
                    .prependTo(settings.button_parent);

    $field.data('text-field-editor', $editor);

    var $handle = $('<div class="zp-text-field-editor-handle">' +
                      '<div class="zp-text-field-editor-icon pen" />' +
                    '</div>').appendTo($editor);

    var $panel = $('<div class="zp-text-field-editor-panel" />')
                   .appendTo($editor);

    var $row = $('<div class="zp-text-field-editor-row">' +
                   '<div class="zp-text-field-editor-icon color-picker" />' +
                 '</div>').appendTo($panel);

    var $options = $('<div class="zp-text-field-editor-options" />')
                     .appendTo($row);

    $('<div class="zp-text-field-editor-clear" />').appendTo($row);

    var name = 'zp-text-field-editor-colorpicker-'
                                              + this.attr('name').substring(12);

    var $radio_button = $('<input type="radio" name="' + name + '" value="default" />');

    $('<div class="zp-text-field-editor-option">' +
        '<div><input type="radio" name="' + name + '" value="default" checked="checked" /></div>' +
        '<div><span>Default</span></div>' +
      '</div>').appendTo($options);

    var $color_picker = $('<div class="zp-text-field-editor-color-example" />');

    var $radio_button = $('<input type="radio" name="' + name + '" value="" />');

    $('<div class="zp-text-field-editor-option" />')
      .append($radio_button.wrap('<div />').parent())
      .append($color_picker).appendTo($options);

    $handle.click(function () {
      if ($editor.hasClass('opened')) {
        $editor.removeClass('opened');

        $(window).unbind('click', out_editor_click);
      } else {
        $('div.zp-text-field-editor').removeClass('opened');

        $editor.addClass('opened');

        $(window).click(out_editor_click);
      }

      return false;
    });

    $('input', $row).change(function () {
      var value = $(this).val();

      if (!value)
        $color_picker.click()
      else if (value == 'default')
        settings.change({ color: undefined } );
      else
        settings.change({ color: value } );
    });

    var color_picker_on = false;

    $color_picker.ColorPicker({
      color: '#804080',

      onBeforeShow: function (colpkr) {
        color_picker_on = true;

        var colour = $radio_button.val();
        if (colour)
          $(this).ColorPickerSetColor(colour);

        $(colpkr).draggable();
        return false;
      },

      onShow: function (colpkr) {
        $(colpkr).fadeIn(500);

        return false;
      },

      onHide: function (colpkr) {
        $(colpkr).fadeOut(500);

        color_picker_on = false;

        return false;
      },

      onSubmit: function (hsb, hex, rgb, el) {
        $color_picker.css('backgroundColor', '#' + hex);
        $radio_button.val('#' + hex).attr('checked', 1);
        $(el).ColorPickerHide();

        settings.change({ color: '#' + hex } );
      }
    });

    function out_editor_click (event) {
      if (color_picker_on)
        return;

      var editor = $editor.get(0);
      var child_parent = $(event.target)
                          .parents('div.zp-text-field-editor')
                          .get(0);

      if (!((event.target == editor) || (child_parent == editor))) {
        $handle.click();
      }
    }

    return this;
  };
})(jQuery);

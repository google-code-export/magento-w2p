function fancybox_add_save_image_button ($, zp, in_preview, name, guid) {
  //Don't add the button if it exists
  if ($('#zp-save-image-button').length)
    return;

  var $outer = $('#fancybox-outer');

  var $button = $('<a id="zp-save-image-button" class="disabled">' +
                    '<span class="icon left-part" />' +
                    '<span class="text">' +
                      '<span class="save-image-text">' + save_text + '</span>' +
                      '<span class="saved-image-text">' + saved_text + '</span>' +
                    '</span>' +
                  '</a>').appendTo($outer);

  var $close = $('#fancybox-close').addClass('resizer-tweaks');

  if (in_preview) {
    $close
      .clone()
      .css('display', 'inline')
      .click(function () {
        $('#zetaprints-preview-image-container')
          .find(' > .zetaprints-field-shape[title="' + name + '"] > .top')
          .click();

        $(this).remove();
        $close.attr('id', 'fancybox-close');
      })
      .appendTo($outer);

    $close.attr('id', 'fancybox-close-orig');
  }

  $button.addClass('no-middle')

  $button.click(function () {
    if ($button.hasClass('disabled'))
      return;

    zp
      .image_edit
      .save();

    zp
      .image_edit
      .$input
      .attr('checked', 'checked')
      .change();

    $outer.addClass('saved');
    $button.addClass('disabled');
  })
}

function fancybox_update_save_image_button ($, changed) {
  if (changed === undefined) {
    $('#zp-save-image-button').addClass('disabled');
    $('#fancybox-outer').removeClass('saved');

    return;
  }

  if (changed) {
    $('#fancybox-outer').removeClass('saved');
    $('#zp-save-image-button').removeClass('disabled');
  }
  else {
    $('#zp-save-image-button').addClass('disabled');
    $('#fancybox-outer').addClass('saved');
  }
}

function fancybox_remove_save_image_button ($) {
  $('#zp-save-image-button').remove();
  $('#fancybox-outer').removeClass('saved');
}

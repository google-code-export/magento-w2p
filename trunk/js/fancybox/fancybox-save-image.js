function fancybox_add_save_image_button ($, zp, in_preview, name, guid) {
  //Don't add the button if it exists
  if ($('#zp-save-image-button').length)
    return;

  var $outer = $('#fancybox-outer')
                 .addClass('saved');

  var $button = $('<a id="zp-save-image-button">' +
                    '<span class="icon left-part" />' +
                    '<span class="text">' +
                      '<span>' + save_text + '</span>' +
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
    if ($outer.hasClass('selected'))
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
  })
}

function fancybox_update_save_image_button ($, changed) {
  if (changed)
    $('#fancybox-outer').removeClass('saved');
  else
    $('#fancybox-outer').addClass('saved');
}

function fancybox_remove_save_image_button ($) {
  $('#zp-save-image-button').remove();
}


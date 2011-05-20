function fancybox_add_update_preview_button ($, zp) {
  var $outer = $('#fancybox-outer');

  var $update_preview = $('<a id="zp-update-preview-button">' +
                            '<span class="icon"/>' +
                            '<span class="text">' +
                              '<span>' + update_preview_button_text + '</span>' +
                            '</span>' +
                          '</a>').appendTo($outer);

  var $fancybox_resize = $('#fancybox-resize')

  if ($fancybox_resize.length)
    $fancybox_resize.addClass('middle-position');
  else {
    $('#fancybox-close').addClass('resizer-tweaks');
    $update_preview.addClass('no-middle');
  }

  $update_preview.click(function () {
    if ($outer.hasClass('modified'))
      zp.update_preview({ data: { zp: zp } });
  })
}

function fancybox_remove_update_preview_button ($) {
  $('#zp-update-preview-button').remove();
  $('#fancybox-resize').removeClass('middle-position');
}


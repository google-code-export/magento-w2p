function zetaprint_image_editor ($, params) {
  var settings = {
    save: function (data) {}
  };

  $.extend(settings, params);

  var context = this;

  var $container = $('.zetaprints-image-edit');

  load_image();

  var $info_bar = $container.find('div.info-bar');

  var info_bar_elements = {
    'current': {
      'width': $('#current-width'),
      'height': $('#current-height'),
      'dpi': $('#current-dpi') },

    'recommended': {
      'width': $('#recommended-width'),
      'height': $('#recommended-height'),
      'dpi': $('#recommended-dpi') } };

  set_info_bar_value('recommended', 'width', context.placeholder.width);
  set_info_bar_value('recommended', 'height', context.placeholder.height);

  if (context.has_fit_in_field) {

    //Calculate shape dimensions
    context.shape.width = context.shape.x2 - context.shape.x1;
    context.shape.height = context.shape.y2 - context.shape.y1;

    context.placeholder.width_in = context.page.width_in * context.shape.width;
    context.placeholder.dpi
                     = context.placeholder.width / context.placeholder.width_in;

    //Calculate ratio of the placeholder
    context.placeholder.ratio =
                         context.placeholder.width / context.placeholder.height;

    set_info_bar_value('recommended', 'dpi', Math.round(context.placeholder.dpi));

    $('#zp-image-edit-action-fit-image').click(function () {
      clear_editor();

      var image
               = fit_image_into_placeholder(context.image, context.placeholder);

      image.ratio = context.image.ratio;

      var data = fit_into_container(image,
                                    context.placeholder,
                                    context.container)

      $container.addClass('changed');

      show_crop(data);
    });

    $('#zp-image-edit-action-fill-field').click(function () {
      clear_editor();

      var placeholder = fill_placeholder_with_image(context.image,
                                                    context.placeholder);

      placeholder.ratio = context.placeholder.ratio;

      var data = fit_into_container(context.image,
                                    placeholder,
                                    context.container)

      $container.addClass('changed');

      show_crop(data);
    });

    $('#zp-image-edit-action-fit-width').click(function () {
      clear_editor();

      var image = fit_image_into_placeholder_by_width(context.image,
                                                      context.placeholder);

      image.ratio = context.image.ratio;

      var data = fit_into_container(image,
                                    context.placeholder,
                                    context.container)

      $container.addClass('changed');

      show_crop(data);
    });

    $('#zp-image-edit-action-fit-height').click(function () {
      clear_editor();

      var image = fit_image_into_placeholder_by_height(context.image,
                                                       context.placeholder);

      image.ratio = context.image.ratio;

      var data = fit_into_container(image,
                                    context.placeholder,
                                    context.container)

      $container.addClass('changed');

      show_crop(data);
    });
  } else
    $container
      .addClass('no-dpi')
      .children('.zetaprints-image-edit-menu')
      .children('.fit-to-field-button-wrapper, .note')
      .hide();

  var $user_image_container = $('#zetaprints-image-edit-container');

  var $user_image= $('#zetaprints-image-edit-user-image')
    .load(function () {
      if ($container.hasClass('crop-mode') || !context.has_fit_in_field)
        crop_button_click_handler();
      else if ($container.hasClass('fit-to-field-mode'))
        fit_to_field_button_click_handler();
      else if ($container.hasClass('editor-mode')) {
        show_image_editor();
      }

      $.fancybox.hideActivity();

      $('#fancybox-overlay').css('z-index', 1100);
    });

  context.container = {
    width: $user_image_container.width() - 2,
    height: $user_image_container.height() - 2
  }

  $('#crop-button').click(crop_button_click_handler);
  $('#fit-to-field-button').click(fit_to_field_button_click_handler);

  $('#undo-button').click(restore_image);

  $('#zp-image-edit-action-cancel').click(function () {
    if ($container.hasClass('changed'))
      if ($container.hasClass('crop-mode'))
        crop_button_click_handler();
      else
        fit_to_field_button_click_handler(true);
  });

  $('#rotate-right-button').click(function () {
    server_side_rotation('r');
  });

  $('#rotate-left-button').click(function () {
    server_side_rotation('l');
  });

  $('#delete-button').click(delete_image);

  $('#image-editor-button').click(image_editor_button_handler);

  function cropping_callback (data) {
    var width_factor = data.selection.width / data.image.width;
    var height_factor = data.selection.height /data.image.height;

    set_info_bar_value('current', 'width',
                                Math.round(context.image.width * width_factor));
    set_info_bar_value('current', 'height',
                              Math.round(context.image.height * height_factor));

    if (width_factor != 1 || height_factor != 1) {
      set_info_bar_state('cropped', true);

      $container.addClass('changed');

      if (window.fancybox_update_save_image_button)
        fancybox_update_save_image_button($, true);
    } else {
      set_info_bar_state();

      $container.removeClass('changed');

      if (window.fancybox_update_save_image_button)
        fancybox_update_save_image_button($);
    }
  }

  function fit_in_field_callback (data) {
    if (window.fancybox_update_save_image_button)
      fancybox_update_save_image_button($, true);

    $container.addClass('changed');

    update_info_bar_values(data);
  }

  function update_info_bar_values (data) {
    var factor = data.selection.width / data.image.width;

    var dpi = factor * context.image.dpi / context.placeholder_to_image_factor;

    if (dpi < context.placeholder.dpi)
      set_info_bar_warning('low-cropped-resolution-warning');
    else
      set_info_bar_warning();

    set_info_bar_value('current', 'dpi', Math.round(dpi));

    var limited_image_width = limit_a_to_b(data.selection.position.left,
                                           data.selection.width,
                                           data.image.position.left,
                                           data.image.width);

    var limited_image_height = limit_a_to_b(data.image.position.top,
                                            data.image.height,
                                            data.selection.position.top,
                                            data.selection.height);

    if ((limited_image_height != data.image.height
         || limited_image_width != data.image.width)
         && limited_image_width != 0 && limited_image_height != 0) {

      var width_factor = limited_image_width / data.image.width;

      var width = context.image.width * width_factor;
      var height = width / context.placeholder.ratio;

      set_info_bar_state('cropped', true);
    } else {
      var width = context.image.width;
      var height = context.image.height;

      set_info_bar_state();
    }

    set_info_bar_value('current', 'width', Math.round(width));
    set_info_bar_value('current', 'height', Math.round(height));
  }

  function update_editor_state (data) {
    if (window.fancybox_update_save_image_button)
      fancybox_update_save_image_button($, true);

    update_info_bar_values(data);
  }

  this.save = function () {
    if ($container.hasClass('crop-mode')) {
      $.fancybox.showActivity();
      server_side_cropping($user_image.power_crop('state'));

      return;
    }

    if ($container.hasClass('fit-to-field-mode')) {
      save_metadata($user_image.power_crop('state'));

      return;
    }

    if ($container.hasClass('editor-mode') && window._zp_image_editor) {
      window._zp_image_editor.save();
    }
  }

  function save_metadata (data) {
    var image = data.image.position;
    image.width = data.image.width;
    image.height =  data.image.height;
    image.right = image.left + image.width;
    image.bottom = image.top + image.height;

    var selection = data.selection.position;
    selection.width = data.selection.width;
    selection.height = data.selection.height;
    selection.right = selection.left + selection.width;
    selection.bottom = selection.top + selection.height;

    if (selection.left < image.left) {
      var shift_x1 = image.left - selection.left;
      var shift_x1 = shift_x1 / selection.width;
      var abs_x1 = context.shape.x1 + context.shape.width * shift_x1;
        
      selection.left = image.left;
    } else
      var abs_x1 = context.shape.x1;

    if (selection.top < image.top) {
      var shift_y1 = image.top - selection.top;
      var shift_y1 = shift_y1 / selection.height;
      var abs_y1 = context.shape.y1 + context.shape.height * shift_y1;

      selection.top = image.top;
    } else
      var abs_y1 = context.shape.y1;
      
    if (selection.right > image.right) {
      var shift_x2 = image.right - selection.right;
      var shift_x2 = shift_x2 / selection.width;
      var abs_x2 = context.shape.x2 + context.shape.width * shift_x2;

      selection.right = image.right;
    }
    else
      var abs_x2 = context.shape.x2;

    if (selection.bottom > image.bottom) {
      var shift_y2 = image.bottom - selection.bottom;
      var shift_y2 = shift_y2 / selection.height;
      var abs_y2 = context.shape.y2 + context.shape.height * shift_y2;

      selection.bottom = image.bottom;
    } else
      var abs_y2 = context.shape.y2;

    var metadata = {
      'cr-x1': (selection.left - image.left) / image.width,
      'cr-x2': (selection.right - image.left) / image.width,
      'cr-y1': (selection.top - image.top) / image.height,
      'cr-y2': (selection.bottom - image.top) / image.height,
      'abs-x1': abs_x1,
      'abs-y1': abs_y1,
      'abs-x2': abs_x2,
      'abs-y2': abs_y2
    };

    context.$input.data('metadata', metadata);

    settings.save(metadata);

    hide_cropped_area_on_thumb();
    show_cropped_area_on_thumb(metadata);
  }

  function clear_metadata () {
    context.$input.removeData('metadata');
    settings.save();

    hide_cropped_area_on_thumb();

    set_info_bar_value('current', 'width', context.image.width);
    set_info_bar_value('current', 'height', context.image.height);
    set_info_bar_value('current', 'dpi', context.image.dpi);
  }

  function server_side_cropping (data) {
    $('#fancybox-overlay').css('z-index', 1103);

    $.ajax({
      url: context.url.image,
      type: 'POST',
      data: {
        'zetaprints-CropX1': data.selection.position.left
                                                     / context.container.factor,
        'zetaprints-CropY1': data.selection.position.top
                                                     / context.container.factor,
        'zetaprints-CropX2': (data.selection.position.left
                             + data.selection.width) / context.container.factor,
        'zetaprints-CropY2': (data.selection.position.top
                            + data.selection.height) / context.container.factor,
        'zetaprints-action': 'img-crop',
        'zetaprints-ImageID': context.image_id
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(cant_crop_image_text + ': ' + textStatus);
        $('#fancybox-overlay').css('z-index', 1100);
      },
      success: function (data, textStatus) {
        clear_metadata();
        clear_editor();
        process_image_details(data);
      }
    });
  }

  /**
   * Perform image restore
   */
  function restore_image () {
    $('#fancybox-overlay').css('z-index', 1103);
    $.fancybox.showActivity();

    clear_editor();
    clear_metadata();

    $.ajax({
    url: context.url.image,
    type: 'POST',
    data: {
      'zetaprints-action': 'img-restore',
      'zetaprints-ImageID': context.image_id
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert(cant_restore_image_text + ': ' + textStatus);
      $('#fancybox-overlay').css('z-index', 1100);
    },
    success: function (data, textStatus) {
      process_image_details(data);
    }
    });
  }

  function reload_image (id) {
    $.ajax({
      url: context.url.image,
      type: 'POST',
      datatype: 'XML',
      data: {
        'zetaprints-action': 'img',
        'zetaprints-ImageID': id
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(cant_load_image_text + ': ' + textStatus);
        $('#fancybox-overlay').css('z-index', 1100);
      },
      success: function (data, textStatus) {
        context.image_id = id;

        process_image_details(data);
      }
    });
  }

  this.reload_image = reload_image;

  function load_image () {
    $('#fancybox-overlay').css('z-index', 1103);
    $.fancybox.showActivity();

    reload_image(context.image_id);
  }

  function server_side_rotation (direction) {
    $('#fancybox-overlay').css('z-index', 1103);

    clear_editor();
    clear_metadata();
    $.fancybox.showActivity();

    $.ajax({
      url: context.url.image,
      type: 'POST',
      data: {
        'zetaprints-action': 'img-rotate',
        'zetaprints-Rotation': direction,
        'zetaprints-ImageID': context.image_id
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(cant_rotate_image_text + ': ' + textStatus);
        $('#fancybox-overlay').css('z-index', 1100);
      },
      success: function (data, textStatus) {
        process_image_details(data);
      }
    });
  }

  function process_image_details (xml) {
    var source = context
                   .url
                   .user_image_template
                   .replace('image-guid.image-ext',
                                  get_value_by_regexp(xml, /Thumb="([^"]*?)"/));

    var preview_width = get_value_by_regexp(xml, /ThumbWidth="([^"]*?)"/);
    var preview_height = get_value_by_regexp(xml, /ThumbHeight="([^"]*?)"/);
    var width = get_value_by_regexp(xml, /ImageWidth="([^"]*?)"/);
    var height = get_value_by_regexp(xml, /ImageHeight="([^"]*?)"/);
    var undo_width = get_value_by_regexp(xml, /ImageWidthUndo="([^"]*?)"/);
    var undo_height = get_value_by_regexp(xml, /ImageHeightUndo="([^"]*?)"/);

    if (!(undo_width && undo_height))
      $('#undo-button')
        .parent()
        .addClass('hidden');
    else
      $('#undo-button')
        .parent()
          .removeClass('hidden')
        .end()
        .attr('title', undo_all_changes_text + '. ' + original_size_text + ': '
          + undo_width + ' x ' + undo_height + ' ' + px_text);

    if (!(preview_width && preview_height && width && height)) {
      alert(unknown_error_occured_text);
      return false;
    }

    context.image = {
      width : width * 1,
      height: height * 1,
      ratio: (width * 1) / (height * 1),
      width_in: (width * 1) / context.placeholder.width
                  * context.placeholder.width_in,
      thumb_width: preview_width,
      thumb_height: preview_height
    };

    context.image.dpi
                     = Math.round(context.image.width / context.image.width_in);

    context.placeholder_to_image_factor
                              = context.placeholder.width / context.image.width;

    set_info_bar_value('current', 'width', context.image.width);
    set_info_bar_value('current', 'height', context.image.height);
    set_info_bar_value('current', 'dpi', context.image.dpi);

    $user_image
      .addClass('zetaprints-hidden')
      .attr('src', source);

    var tmp1 = $('input[value="' + context.image_id + '"]').parent().find('img');
    if (tmp1.length == 0)
      tmp1 = $('#img' + context.image_id);
    if (tmp1.length == 0)
      tmp1 = $('input[value="' + context.image_id + '"]').parent().find('img');
    if (source.match(/\.jpg/m))
      tmp1.attr('src', source.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));
    else
      tmp1.attr('src', source);
  }

  function delete_image () {
    if (confirm(delete_this_image_text))
      $.ajax({
        url: context.url.image,
        type: 'POST',
        data: {
          'zetaprints-action': 'img-delete',
          'zetaprints-ImageID': context.image_id
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(cant_delete_text + ': ' + textStatus);
        },
        success: function (data, textStatus) {
          clear_editor();
          clear_metadata();

          $('input[value="' + context.image_id +'"]').parent().remove();
          $('#' + context.image_id).remove();

          $.fancybox.close();
        }
      });
  }

  function set_info_bar_value (type, key, value) {
    info_bar_elements[type][key].html(value);
  }

  function set_info_bar_warning (warning) {
    if (warning)
      $info_bar.addClass('warning ' + warning);
    else
      $info_bar.removeClass('warning low-resolution-warning ' +
                            'low-cropped-resolution-warning ' +
                            'low-full-resolution-warning small-image-warning');
  }

  function set_info_bar_state (state, on) {
    if (!state) {
      $info_bar.removeClass('cropped-state');
    }

    if (on)
      $info_bar.addClass(state + '-state');
    else
      $info_bar.removeClass(state + '-state');
  }

  function limit_a_to_b (start_a, length_a, start_b, length_b) {
    if (length_a == 0)
      return 0;

    var end_a = start_a + length_a;
    var end_b = start_b + length_b;

    if (start_a >= end_b || end_a <= start_b)
      return 0;

    if (start_a < start_b)
      start_a = start_b;

    if (end_a > end_b)
      end_a = end_b;

    return end_a - start_a;
  }

  function get_factor_a_to_b (width_a, height_a, width_b, height_b) {
    var width_factor = width_a / width_b;
    var height_factor = height_a / height_b;

    return width_factor < height_factor ? width_factor : height_factor;
  }

  function fit_image_into_placeholder (image, placeholder) {
    var factor = get_factor_a_to_b(placeholder.width, placeholder.height,
                                   image.width, image.height);

    return { width: image.width * factor, height: image.height * factor };
  }

  function fill_placeholder_with_image (image, placeholder) {
    var factor = get_factor_a_to_b(image.width, image.height,
                                   placeholder.width, placeholder.height);

    return { width: placeholder.width * factor,
             height: placeholder.height * factor };
  }

  function fit_image_into_placeholder_by_width (image, placeholder) {
    var factor = placeholder.width / image.width;

    return { width: placeholder.width, height: image.height * factor };
  }

  function fit_image_into_placeholder_by_height (image, placeholder) {
    var factor = placeholder.height / image.height;

    return { width: image.width * factor, height: placeholder.height };
  }

  function fit_into_container (image, placeholder, container) {
    var data = {
      selection: {
        position: {
          top: 0,
          left: 0
        }
      },

      image: {
        position: {
          top: 0,
          left: 0
        }
      }
    };

    //Use container's factor to convert original dimension to
    //container's one (multiply by the factor) or vice versa (divide by the factor)
    if (placeholder.width >= image.width
        && placeholder.height >= image.height)
      container.factor = get_factor_a_to_b(container.width,
                                           container.height,
                                           placeholder.width,
                                           placeholder.height);

    else
      container.factor = get_factor_a_to_b(container.width,
                                           container.height,
                                           image.width,
                                           image.height);

    data.selection.width = Math.round(placeholder.width * container.factor);
    data.selection.height = data.selection.width / placeholder.ratio;

    data.image.width = Math.round(image.width * container.factor);
    data.image.height = data.image.width / image.ratio;

    //Centring selection frame and image to centre of the container
    var width_centre = container.width / 2;
    var height_centre = container.height / 2;

    data.selection.position.left = (width_centre - data.selection.width / 2);
    data.selection.position.top = (height_centre - data.selection.height / 2);

    data.image.position.left = (width_centre - data.image.width / 2);
    data.image.position.top = (height_centre - data.image.height / 2);

    return data;
  }

  function fit_into_container_using_metadata (image, placeholder, shape,
                                              container, metadata) {
    data = {
      selection: {
        position: {
          top: 0,
          left: 0
        },
        width: placeholder.width,
        height: placeholder.height
      },

      image: {
        position: {
          top: 0,
          left: 0
        },
        width: image.width,
        height: image.height  
      }
    };

    if (metadata['abs-x1'] <= shape.x1 && metadata['abs-x2'] >= shape.x2
        && metadata['abs-y1'] <= shape.y1 && metadata['abs-y2'] >= shape.y2) {
      data.selection.position.left = metadata['cr-x1'] * image.width;
      data.selection.position.top = metadata['cr-y1'] * image.height;

      data.selection.width = (metadata['cr-x2'] - metadata['cr-x1']) * image.width;
      data.selection.height = data.selection.width / placeholder.ratio;
    } else {
      data.image.position.left = placeholder.width
                                * (metadata['abs-x1'] - shape.x1) / shape.width;
      data.image.position.top = placeholder.height
                               * (metadata['abs-y1'] - shape.y1) / shape.height;

      data.image.width = placeholder.width
                       * (metadata['abs-x2'] - metadata['abs-x1']) / shape.width
                       * (1 + metadata['cr-x1'] / (1 - metadata['cr-x1'])
                            + (1 - metadata['cr-x2']) / metadata['cr-x2']);
      data.image.height = data.image.width / image.ratio;

      data.selection.position.left = data.image.width * metadata['cr-x1'];
      data.selection.position.top = data.image.height * metadata['cr-y1'];
    }

    var left = data.selection.position.left < data.image.position.left
                ? data.selection.position.left : data.image.position.left;

    var top = data.selection.position.top < data.image.position.top
                ? data.selection.position.top : data.image.position.top

    data.selection.position.right = data.selection.position.left
                                                         + data.selection.width;

    data.image.position.right = data.image.position.left + data.image.width;

    var right = data.selection.position.right > data.image.position.right
                  ? data.selection.position.right : data.image.position.right;

    data.selection.position.bottom = data.selection.position.top
                                                        + data.selection.height;

    data.image.position.bottom = data.image.position.top + data.image.height;

    var bottom = data.selection.position.bottom > data.image.position.bottom
                   ? data.selection.position.bottom
                     : data.image.position.bottom;

    var total_width = right - left;
    var total_height = bottom - top;

    //Use container's factor to convert original dimension to
    //container's one (multiply by the factor)
    //or vice versa (divide by the factor)
    container.factor = get_factor_a_to_b(container.width,
                                         container.height,
                                         total_width,
                                         total_height);

    data.selection.width *= container.factor;
    data.selection.height *= container.factor;
    data.selection.position.left *= container.factor;
    data.selection.position.top *= container.factor;

    data.image.width *= container.factor;
    data.image.height = data.image.width / image.ratio;
    data.image.position.left *= container.factor;
    data.image.position.top *= container.factor;

    var shift_x = (container.width - total_width * container.factor) / 2;
    var shift_y = (container.height - total_height * container.factor) /2;

    data.selection.position.left += shift_x;
    data.selection.position.top += shift_y;

    data.image.position.left += shift_x;
    data.image.position.top += shift_y;

    return data;
  }

  function fit_into_container_for_crop (image, container) {
    container.factor = get_factor_a_to_b(container.width,
                                         container.height,
                                         image.thumb_width,
                                         image.thumb_height);

    var factor = get_factor_a_to_b(container.width,
                                   container.height,
                                   image.width,
                                   image.height);

    var width = image.width * factor;
    var height = width / image.ratio;

    //Centring selection frame and image to centre of the container
    var width_centre = container.width / 2;
    var height_centre = container.height / 2;

    var left = (container.width / 2 - width / 2);
    var top = (container.height / 2 - height / 2);

    data = {
      selection: {
        position: {
          top: 0,
          left: 0
        },
        width: width,
        height: height
      },

      image: {
        position: {
          top: 0,
          left: 0
        },
        width: width,
        height: height
      },

      container: {
        top: top,
        left: left,
        width: width,
        height: height
      }
    };

    return data;
  }

  function show_crop (data, simple_crop) {
    if (!$.fn.power_crop)
      return;

    $user_image.power_crop({
      simple: simple_crop == true,
      data: data,
      crop: simple_crop ? cropping_callback : fit_in_field_callback
    });

    if (!simple_crop) {
      data = $user_image.power_crop('state');

      update_editor_state(data);
    }
  }

  function clear_editor () {
    if ($container.hasClass('crop-mode')
        || $container.hasClass('fit-to-field-mode'))
      $user_image.power_crop('destroy');

    if ($container.hasClass('editor-mode') && window._zp_image_editor) {
      window._zp_image_editor.close();
    }

    $container.removeClass('changed');

    set_info_bar_warning();
    set_info_bar_state();

    if (window.fancybox_update_save_image_button)
      fancybox_update_save_image_button($);
  }

  function get_value_by_regexp (subject, exp) {
    match = subject.match(exp);
    if (match != null) {
      if (match.length > 2)
        return match;
      else
        return match[1];
    }
    else
      return false;
  }

  function crop_button_click_handler () {
    clear_editor();

    $container
      .removeClass('fit-to-field-mode editor-mode')
      .addClass('crop-mode');

    //if (window.fancybox_update_save_image_button)
    //    fancybox_update_save_image_button($);

    var data = fit_into_container_for_crop (context.image, context.container);

    show_crop(data, true);
  }

  function fit_to_field_button_click_handler (ignore_metadata) {
    clear_editor();

    $container
      .removeClass('crop-mode editor-mode')
      .addClass('fit-to-field-mode');

    var metadata = context.$input.data('metadata');

    if (!metadata || ignore_metadata)
      var data = fit_into_container(context.image,
                                    context.placeholder,
                                    context.container);
    else {
      var data = fit_into_container_using_metadata (context.image,
                                                    context.placeholder,
                                                    context.shape,
                                                    context.container,
                                                    metadata);

      $container.addClass('changed');
    }

    show_crop(data);

    if (window.fancybox_update_save_image_button)
      fancybox_update_save_image_button($, !metadata || ignore_metadata);
  }

  function image_editor_button_handler () {
    if ($container.hasClass('editor-mode'))
      return;

    clear_editor();
    clear_metadata();

    $container
      .removeClass('crop-mode fit-to-field-mode')
      .addClass('editor-mode');

    show_image_editor();
  }

  function show_image_editor () {
    var $edit_container = $('#zetaprints-image-edit-container');

    var fancybox_center_function = $.fancybox.center;

    $.fancybox.center = function () {
      fancybox_center_function();

      var offset = $edit_container.offset();

      window
        ._zp_image_editor_wrapper
        .css({
          top: offset.top,
          left: offset.left
        });
  }

    if (!window._zp_image_editor) {
      $('#fancybox-overlay').css('z-index', 1103);
      $.fancybox.showActivity();

      _zp_image_editor_wrapper = $('<div id="zp-image-edit-editor-wrapper" />')
                                 .appendTo('body');

      _zp_image_editor_wrapper
        .css({
          top: $edit_container.offset().top,
          left: $edit_container.offset().left,
          width: $edit_container.outerWidth(),
          height: $edit_container.outerHeight()
        });

      _zp_image_editor = new Aviary.Feather({
        image: 'zetaprints-image-edit-user-image',
        apiVersion: 2,
        appendTo: 'zp-image-edit-editor-wrapper',
        language: $('html').attr('lang'),
        url: $user_image.attr('src'),
        minimumStyling: true,
        noCloseButton: true,
        jpgQuality: 100,
        maxSize: 600,
        onSave: function(image_id, url) {
          context.upload_image_by_url(url);

          return false;
        },
        onLoad: function () {
          window._zp_image_editor.launch();

          $.fancybox.hideActivity();
          $('#fancybox-overlay').css('z-index', 1100);
        },
        onReady: function () {
          $container.addClass('changed');

          if (window.fancybox_update_save_image_button)
            fancybox_update_save_image_button($, true);
        },
        onClose: function () {
          window
            ._zp_image_editor_wrapper
            .css('display', 'none');
        }
      });
    } else {
      var offset = $edit_container.offset();

      window
        ._zp_image_editor_wrapper
        .css({
          display: 'block',
          top: offset.top,
          left: offset.left
        });

      window._zp_image_editor.launch({image: 'zetaprints-image-edit-user-image',
                                    url: $user_image.attr('src')});
    }
  }

  function show_cropped_area_on_thumb (data) {
    var left = data['cr-x1'] * 100;
    var top = data['cr-y1'] * 100;
    var width = (data['cr-x2'] - data['cr-x1']) * 100;
    var height = (data['cr-y2'] - data['cr-y1']) * 100;

    $img = context
      .$selected_thumbnail
      .clone();
    
    var position = $img
      .wrap('<div class="top-image-wrapper" />')
      .parent()
        .css({
          left: left + '%',
          top: top + '%',
          width: width + '%',
          height: height + '%'
        })
        .wrap('<div class="thumb-shadow" />')
        .parent()
          .insertAfter(context.$selected_thumbnail)
        .end()
      .end()
      .position();

    $img.css({
      left: -position.left,
      top: -position.top
    });
  }

  function hide_cropped_area_on_thumb () {
    context
      .$selected_thumbnail
      .parent()
      .children('.thumb-shadow')
      .remove();
  }
}

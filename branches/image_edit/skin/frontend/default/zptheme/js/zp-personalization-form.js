function personalization_form () {
  var $ = jQuery;

  function scroll_strip(panel) {
    if ($(panel).hasClass('images-scroller')) {
      $(panel).scrollLeft(0);
      var position = $('input:checked', panel).parents('td').position();
      if (position)
        $(panel).scrollLeft(position.left);
    }
    return true;
  }

  var product_image_box = $('#zetaprints-preview-image-container').css('position', 'relative')[0];
  var product_image_element = $('#image').parent()[0];
  var has_image_zoomer = $(product_image_element).hasClass('product-image-zoom');

  //If there's previews for the product
  if (previews.length) {

    //and base image is not set
    if (!has_image_zoomer)
      //then remove all original images placed by M.
      $(product_image_element).empty();

    //and it's personalization step (for 2-step theme)
    if (is_personalization_step) {
      //remove zoomer and base image
      $(product_image_element).removeClass('product-image-zoom');
      $('#image, #track_hint, div.zoom').remove();
      has_image_zoomer = false;
    }
  }

  //Add placeholders with spinners for preview images to the product page
  for (var page_number = 1; page_number <=  previews.length; page_number++)
    $('<div id="zp-placeholder-for-preview-' + page_number +
      '" class="zetaprints-preview-placeholder hidden"><div class=' +
      '"zetaprints-big-spinner" /></div>').appendTo(product_image_element);

  //If no image zoomer on the page
  if (!has_image_zoomer)
    //then show placeholder and spinner for the first page
    $('#zp-placeholder-for-preview-1').removeClass('hidden');

  //Set current template page to the first (1-based index)
  current_page = 1;

  //Add TemplateID parameter to the form
  $('<input type="hidden" name="zetaprints-TemplateID" value="' +
    template_id +'" />').appendTo('#product_addtocart_form');

  //If update_first_preview_on_load parameter was set
  if (update_first_preview_on_load) {
    //Add over-image spinner for the first preview
    $('<div id="zetaprints-first-preview-update-spinner" class="' +
      'zetaprints-big-spinner zetaprints-over-image-spinner hidden" />')
      .appendTo(product_image_box);

    //Update preview for the first page
    update_preview(null, true);
  }

  //Add previews to the product page
  for (var page_number = 1; page_number <=  previews.length; page_number++)
    $('<a id="preview-image-page-' + page_number +
      '" class="zetaprints-template-preview  hidden" href="' +
      previews[page_number - 1] + '"><img title="' + click_to_view_in_large_size +
      '" src="' + previews[page_number - 1] + '" /></a>')
    .children().bind('load', {page_number: page_number}, function (event) {
      //Hide placeholder and spinner after image has loaded
      $('#zp-placeholder-for-preview-' + event.data.page_number)
        .addClass('hidden');

      //If no image zoomer on the page and image is for the first page
      //and first page was opened
      if (!has_image_zoomer && event.data.page_number == 1
          && current_page == 1)
        //then show preview for the first page
        $('#preview-image-page-1').removeClass('hidden');

      //If update_first_preview_on_load parameter was set and
      //first default preview has already been loaded then...
      if (update_first_preview_on_load && event.data.page_number == 1)
        //...show over-image spinner
        $('div#zetaprints-first-preview-update-spinner')
          .removeClass('hidden');
    }).end().appendTo(product_image_element);

  //Reset previews array if previews was default template preview images
  if (!previews_from_session)
    previews = [];

  $('div.zetaprints-page-stock-images input:checked').each(function() {
    if ($(this).attr('id') != 'zetaprints-blank-value')
      $(this).parents('div.zetaprints-images-selector').removeClass('no-value');
  });

  $('#stock-images-page-1, #input-fields-page-1').removeClass('hidden');
  $('div.zetaprints-image-tabs, div.zetaprints-preview-button').css('display', 'block');

  $('div.zetaprints-image-tabs li:first').addClass('selected');

  $('div.tab.user-images').each(function() {
    var tab_button = $('ul.tab-buttons li.hidden', $(this).parents('div.selector-content'));

    if ($('td', this).length > 0)
      $(tab_button).removeClass('hidden');
  });

  number_of_pages = $('a.zetaprints-template-preview').length;
  changed_pages = new Array(number_of_pages + 1);

  //Create array for preview images sharing links
  if (window.place_preview_image_sharing_link)
    preview_sharing_links = new Array(number_of_pages + 1);

  if (previews_from_session) {
    $('a.zetaprints-image-tabs img').each(function () {
      var src = $(this).attr('src').split('thumb');
      var id = src[1].split('_');
      var n = id[0].substring(38, id[0].length);

      var new_id = previews[n].split('.');
      $(this).attr('src', src[0] + 'thumb/' + new_id[0] + '_100x100.' + new_id[1]);
    });

    $('<input type="hidden" name="zetaprints-previews" value="' + previews.join(',') + '" />').appendTo($('#product_addtocart_form'));
  } else {
    $('<input type="hidden" name="zetaprints-previews" value="" />').appendTo($('#product_addtocart_form'));
    $('#zetaprints-add-to-cart-button').css('display', 'none');
  }

  $('div.zetaprints-image-tabs li').click(function () {
    $('div.zetaprints-image-tabs li').removeClass('selected');

    //Hide preview image, preview placeholder with spinner, text fields
    //and image fields for the current page
    $('a.zetaprints-template-preview, div.zetaprints-page-stock-images, div.zetaprints-page-input-fields, div.zetaprints-preview-placeholder').addClass('hidden');

    //Remove shapes for current page
    if (shapes && window.remove_all_shapes)
      remove_all_shapes(product_image_box);

    $(this).addClass('selected');
    var page = $('img', this).attr('rel');

    //If there's image zoomer on the page
    if (has_image_zoomer) {
      //remove it and base image
      $(product_image_element).removeClass('product-image-zoom');
      $('#image, #track_hint, div.zoom').remove();
      has_image_zoomer = false;
    }

    //Show preview image, preview placeholder with spinner, text fields
    //and image fields for the selected page
    $('#preview-image-' + page + ', #stock-images-' + page + ', #input-fields-'
      + page + ', #zp-placeholder-for-preview-' + page).removeClass('hidden');

    //Remember number of selected page
    current_page = page.split('-')[1] * 1;

    //Set preview images sharing link for the current page
    if (window.place_preview_image_sharing_link)
      set_preview_sharing_link_for_page(current_page, preview_sharing_links)

    //Add shapes for selected page
    if (shapes && window.place_all_precalculated_shapes_for_page && window.shape_handler)
      place_all_precalculated_shapes_for_page(current_page, shapes, product_image_box, shape_handler);

    if (changed_pages[current_page] && page_number < number_of_pages)
      $('div.zetaprints-next-page-button').show();
    else
      $('div.zetaprints-next-page-button').hide();
  });

  function add_preview_sharing_link (filename) {
    preview_sharing_links[current_page] = preview_image_sharing_link_template +
                                          filename;

    $('span.zetaprints-share-link').removeClass('empty');
    $('#zetaprints-share-link-input').val(preview_sharing_links[current_page]);
  }

  function set_preview_sharing_link_for_page (page_number, links) {
    if (links[page_number]) {
      $('span.zetaprints-share-link').removeClass('empty');
      $('#zetaprints-share-link-input').val(links[page_number]);
    } else {
      $('span.zetaprints-share-link').addClass('empty');
      $('#zetaprints-share-link-input').val('');
    }
  }

  function prepare_post_data_for_php (data) {
    var _data = '';

    data = data.split('&');
    for (var i = 0; i < data.length; i++) {
      var token = data[i].split('=');
      _data += '&' + token[0].replace(/\./g, '\x0A') + '=' + token[1];
    }

    return _data.substring(1);
  }

  function update_preview (event, preserve_fields) {
    $('div.zetaprints-preview-button span.text, ' +
      'div.zetaprints-preview-button img.ajax-loader').css('display', 'inline');

    var update_preview_button = $('button.update-preview').hide();

    //Convert preserve_field parameter to query parameter
    var preserve_fields = typeof(preserve_fields) != 'undefined'
      && preserve_fields ? '&zetaprints-Preserve=yes' : preserve_fields = '';

    $.ajax({
      url: preview_controller_url,
      type: 'POST',
      dataType: 'json',
      data: prepare_post_data_for_php($('#product_addtocart_form').serialize())
        + '&zetaprints-From=' + current_page + preserve_fields,
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        $('div.zetaprints-preview-button span.text, img.ajax-loader').css('display', 'none');
        $(update_preview_button).bind('click', update_preview).show();
        alert(preview_generation_response_error_text + textStatus); },
      success: function (data, textStatus) {
        if (!data) {
          alert(preview_generation_error_text);
        } else {
          //Update links to preview image on current page
          $('#preview-image-page-' + current_page).attr('href',
                              data.pages[current_page]['updated-preview-url']);
          $('#preview-image-page-' + current_page + ' img').attr('src',
                              data.pages[current_page]['updated-preview-url']);

          var preview_filename = data.pages[current_page]['updated-preview-url']
                                   .split('/preview/')[1];

          //Generate preview sharing link if it was enabled
          if (window.place_preview_image_sharing_link)
            add_preview_sharing_link(preview_filename);

          //Update link to preview image in opened fancybox
          var fancy_img = $('#fancybox-img');
          if (fancy_img.length)
            $(fancy_img).attr('src',
                              data.pages[current_page]['updated-preview-url']);

          //Remember file name of preview image for current page
          previews[current_page - 1] = preview_filename;

          //Update link to preview thumbnail for current page tab
          $('div.zetaprints-image-tabs img[rel=page-' + current_page + ']')
            .attr('src', data.pages[current_page]['updated-thumb-url']);

          //If there's image zoomer on the page
          if (has_image_zoomer) {
            //remove it and base image
            $(product_image_element).removeClass('product-image-zoom');
            $('#image, #track_hint, div.zoom').remove();
            has_image_zoomer = false;
            //and show preview image for the current page
            $('#preview-image-page-' + current_page).removeClass('hidden');

            //Add all shapes to personalization form after first preview
            //update
            if (shapes && window.place_all_precalculated_shapes_for_page
                && window.shape_handler)
              place_all_precalculated_shapes_for_page(current_page,
                                                      shapes,
                                                      product_image_box,
                                                      shape_handler);
          }

          if (previews.length == number_of_pages) {
            $('input[name=zetaprints-previews]').val(previews.join(','));
            $('#zetaprints-add-to-cart-button').show();
            $('div.save-order span').css('display', 'none');
          }
        }

        changed_pages[current_page] = true;

        if (current_page < number_of_pages)
          $('div.zetaprints-next-page-button').show();
        else
          $('div.zetaprints-next-page-button').hide();

        //If update_first_preview_on_load parameter was set then...
        if (update_first_preview_on_load)
          //.. remove over-image spinner
          $('div#zetaprints-first-preview-update-spinner').remove();

        $('div.zetaprints-preview-button span.text, ' +
          'div.zetaprints-preview-button img.ajax-loader')
            .css('display', 'none');

        $(update_preview_button).show(); }
    });

    return false;
  }

  $('button.update-preview').click(update_preview);

  $('div.button.choose-file').each(function () {
    var uploader = new AjaxUpload(this, {
      name: 'customer-image',
      action: upload_controller_url,
      autoSubmit: false,
      onChange: function (file, extension) {
        var upload_div = $(this._button).parents('div.upload');
        $('input.file-name', upload_div).val(file);
        $('div.button.upload-file', upload_div).removeClass('disabled');
      },
      onSubmit: function (file, extension) {
        var upload_div = $(this._button).parents('div.upload');
        $('div.button.upload-file', upload_div).addClass('disabled');
        $('img.ajax-loader', upload_div).show();
      },
      onComplete: function (file, response) {
        var upload_div = $(this._button).parents('div.upload');

        if (response == 'Error') {
          $('img.ajax-loader', upload_div).hide();
          alert(uploading_image_error_text);
          return;
        }

        var upload_field_id = $(upload_div).parents('div.selector-content').attr('id');

        $('input.file-name', upload_div).val('');

        response = response.split(';');

        var trs = $('div.zetaprints-page-stock-images div.tab.user-images table tr');

        var number_of_loaded_imgs = 0;

        $(trs).each(function () {
          var image_name = $('input[name=parameter]', $(this).parents('div.user-images')).val();

          var td = $('<td><input type="radio" name="zetaprints-#' + image_name
            + '" value="' + response[0]
            + '" /><a class="edit-dialog" href="' + response[1]
            + 'target="_blank"><img src="' + response[2]
            + '" /></a> <div style="float:right;"><a class="edit-dialog" style="float:left" href="'+response[1]
            + '" target="_blank"><div class="edit-button">' + edit_button_text + '</div></a><a class="delete-button" href="javascript:void(1)"><div class="delete-button"></div></a></div>').prependTo(this);

          $('input:radio', td).change(image_field_select_handler);

          var tr = this;

          $('img', td).load(function() {

            //If a field the image was uploaded into is not current image field
            if ($(this).parents('div.selector-content').attr('id') != upload_field_id) {
              var scroll = $(td).parents('div.images-scroller');

              //Scroll stripper to save position of visible images
              $(scroll).scrollLeft($(scroll).scrollLeft() + $(td).outerWidth());
            }

            $('a.edit-dialog', tr).fancybox({
              'padding': 0,
              'titleShow': false,
              'type': 'iframe',
              'hideOnOverlayClick': false,
              'hideOnContentClick': false,
              'centerOnScroll': false });

            $('a.delete-button', td).click(function() {
              var imageId = $(this).parent().prevAll('input').val();

              if (confirm(delete_this_image_text)) {
                $.ajax({
                  url: image_controller_url,
                  type: 'POST',
                  data: 'zetaprints-action=img-delete&zetaprints-ImageID='+imageId,
                  error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(cant_delete_text + ': ' + textStatus);
                  },
                  success: function (data, textStatus) {
                    $('input[value='+imageId+']').parent().remove();
                  }
                });
              }
            });

            if (++number_of_loaded_imgs == trs.length) {
              $('div.tab.user-images input[value=' + response[0] + ']',
                $(upload_div).parent()).attr('checked', 1).change();

              $('img.ajax-loader', upload_div).hide();
              $('div.zetaprints-page-stock-images ul.tab-buttons li.hidden')
                .removeClass('hidden');
              $(upload_div).parents('div.selector-content').tabs('select', 1);
            }
          });
        });
      }
    });

    $('div.button.upload-file', $(this).parent()).click(function () {
      if (!$(this).hasClass('disabled'))
        uploader.submit();
    });
  })

  function image_field_select_handler (event) {
    $(event.target).parents('div.zetaprints-images-selector')
      .removeClass('no-value');

    //If ZetaPrints advanced theme is enabled then...
    if (window.mark_shape_as_edited && window.unmark_shape_as_edited)
      if ($(event.target).val().length)
        //... mark shape as edited then image is seleсted
        mark_shape_as_edited($(event.target).attr('name').substring(12), shapes,
          current_page);
      else
        //or unmark shape then Leave blank is selected
        unmark_shape_as_edited($(event.target).attr('name').substring(12),
          shapes, current_page);
  }

  $(window).load(function () {

    if (shapes && window.mark_shapes_as_edited
        && window.precalculate_shapes
        && window.place_all_precalculated_shapes_for_page && shape_handler) {

      mark_shapes_as_edited(shapes);
      precalculate_shapes(shapes, get_preview_dimensions(number_of_pages));

      //Add all shapes only then there's no base image.
      //Shapes will be added after first preview update then base image exists
      if (!has_image_zoomer)
        place_all_precalculated_shapes_for_page(current_page,
                                                shapes,
                                                product_image_box,
                                                shape_handler);
    }

    $('div.zetaprints-images-selector').each(function () {
      var top_element = this;

      var tab_number = 0
      if ($('li.hidden', this).length == 0)
        tab_number = 1;

      var tabs = $('div.selector-content', this).tabs({
        selected: tab_number,
        show: function (event, ui) {
          if ($(ui.panel).hasClass('color-picker') && !$('input', ui.panel).attr('checked'))
            $('div.color-sample', ui.panel).click();
            scroll_strip(ui.panel);
              }
      });

      $('input', this).change(image_field_select_handler);

      $('div.head', this).click(function () {
        if ($(top_element).hasClass('minimized')) {
          $(top_element).removeClass('minimized');
          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
          if (panel.hasClass('color-picker') && !$('input', panel).attr('checked')) {
            $('div.color-sample', panel).click();
          }
          scroll_strip(panel)
        }
        else
          $(top_element).addClass('minimized').removeClass('expanded').css('width', '100%');

        return false;
      });

      var previews_images = $('div.product-img-box');

      $('a.image.collapse-expand', this).click(function () {
        if ($(top_element).hasClass('expanded')) {
          $(top_element).removeClass('expanded').css('width', '100%');
          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
        } else {
          var position = $(top_element).position().left - $(previews_images).position().left;
          $(top_element).addClass('expanded')
            .css({ 'left': -position, 'width': position + $(top_element).outerWidth() })
            .removeClass('minimized');

          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
          if (panel.hasClass('color-picker') && !$('input', panel).attr('checked')) {
            $('div.color-sample', panel).click();
          }
        }
        scroll_strip(panel);
        return false;
      });

      var input = $('div.color-picker input', this)[0];
      var color_sample = $('div.color-sample', this);

      var colour = $(input).val();
      if (colour)
        $(color_sample).css('backgroundColor', colour);

      $([color_sample, $('div.color-picker a', this)]).ColorPicker({
        color: '#804080',
        onBeforeShow: function (colpkr) {
          var colour = $(input).val();
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
          return false;
        },
        onSubmit: function (hsb, hex, rgb, el) {
          $(top_element).removeClass('no-value');
          $(color_sample).css('backgroundColor', '#' + hex);
          $(input).val('#' + hex).attr('checked', 1).attr('disabled', 0);
          $(el).ColorPickerHide();
        }
      });
    });
  });

  $('div.zetaprints-next-page-button').click(function () {
    var next_page_number = current_page + 1;

    $('div.zetaprints-image-tabs li img[rel=page-' + next_page_number +']').parent().click();

    if (next_page_number >= number_of_pages)
      $(this).hide();

    return false;
  });

  $('a.zetaprints-template-preview').fancybox({
    'opacity': true,
    'overlayShow': false,
    'transitionIn': 'elastic',
    'speedIn': 500,
    'speedOut' : 500,
    'titleShow': false,
    'hideOnContentClick': true,
    'showNavArrows': false,
    'onComplete': function () {
      $('img#fancybox-img').attr('title', click_to_close_text);

      if (!(shapes && window.place_all_shapes_for_page
        && window.highlight_shape_by_name && window.popup_field_by_name
        && window.fancy_shape_handler))
        return;

      var fancy_inner = $('div#fancybox-inner')[0];
      var fancy_image = $('img#fancybox-img', fancy_inner)[0];

      var dimension = {
        width: $(fancy_image).width(),
        height: $(fancy_image).height() };

      place_all_shapes_for_page (current_page, shapes, dimension, fancy_inner,
                                 fancy_shape_handler);

      if (typeof(current_field_name) != 'undefined' && current_field_name != null && current_field_name.length != 0) {
        highlight_shape_by_name(current_field_name, fancy_inner);
        popup_field_by_name(current_field_name);
      }

      current_field_name = null;
    },
    'onCleanup': function () {
      if (shapes && window.popdown_field_by_name) {
        $('div.zetaprints-field-shape', $('div#fancybox-inner')).removeClass('highlighted');
        popdown_field_by_name();
      }
    } });

  $('a.in-dialog').fancybox({
    'opacity': true,
    'overlayShow': false,
    'transitionIn': 'elastic',
    'changeSpeed': 200,
    'speedIn': 500,
    'speedOut' : 500,
    'titleShow': false });

  $('a.edit-dialog').fancybox({
    'padding': 0,
    'titleShow': false,
    'type': 'iframe',
    'hideOnOverlayClick': false,
    'centerOnScroll': true,
    'showNavArrows': false });

  $('div.zetaprints-page-input-fields input[title], div.zetaprints-page-input-fields textarea[title]').qtip({
    position: { corner: { target: 'bottomLeft' } },
        show: { delay: 1, solo: true, when: { event: 'focus' } },
        hide: { when: { event: 'unfocus' } }
  });

  $('div.zetaprints-page-stock-images select[title]').qtip({
    position: { corner: { target: 'topLeft' }, adjust: { y: -30 } },
        show: { delay: 1, solo: true, when: { event: 'focus' } },
        hide: { when: { event: 'unfocus' } }
  });

  $('div.zetaprints-page-input-fields input.input-text').keypress(function (event) {
    if (event.keyCode == 13)
      return false;
  });

  //If ZetaPrints advanced theme is enabled then...
  if (shapes && window.mark_shape_as_edited && window.unmark_shape_as_edited) {
    $('div.zetaprints-page-input-fields :input').keyup(function () {
      if ($(this).val().length)
        // ... then mark shape as edited if input field was modified and is not empty
        mark_shape_as_edited($(this).attr('name').substring(12), shapes, current_page);
      else
        // or unmark it if input field is empty
        unmark_shape_as_edited($(this).attr('name').substring(12), shapes, current_page);
    });
  }

  $('a.delete-button').click(function() {
    var imageId = $(this).parent().prevAll('input').val();

    if (confirm(delete_this_image_text)) {
      $.ajax({
        url: image_controller_url,
        type: 'POST',
        data: 'zetaprints-action=img-delete&zetaprints-ImageID='+imageId,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(cant_delete_text + ': ' + textStatus);
        },
        success: function (data, textStatus) {
          $('input[value='+imageId+']').parent().remove();
        }
      });
    }
  });

  if (shapes && window.add_in_preview_edit_handlers)
    add_in_preview_edit_handlers();
}

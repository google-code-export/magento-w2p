function personalization_form ($) {
  var zp = this;

  function scroll_strip(panel) {
    if ($(panel).hasClass('images-scroller')) {
      $(panel).scrollLeft(0);
      var position = $('input:checked', panel).parents('td').position();
      if (position)
        $(panel).scrollLeft(position.left);
    }
    return true;
  }

  zp.scroll_strip = scroll_strip;

  function save_image_handler (metadata) {
    var $input = zp.image_edit.$input;

    if (!$input.length)
      return;

    if (metadata) {
      metadata['img-id'] = $input.attr('value');

      zp_set_metadata(zp.image_edit.placeholder, metadata);
    } else
      zp_clear_metadata(zp.image_edit.placeholder);
  }

  function show_image_edit_dialog (image_name, image_guid, $thumb) {
    var image_name = unescape(image_name);

    $.fancybox({
      'padding': 0,
      'titleShow': false,
      'type': 'ajax',
      'href': zp.url['edit-image-template'] + image_guid,
      'hideOnOverlayClick': false,
      'hideOnContentClick': false,
      'centerOnScroll': false,
      'showNavArrows': false,
      'onStart' : function () {
        $('#fancybox-overlay').css('z-index', 1103);

        var is_in_preview = false;

        if ($('#zp-update-preview-button').length
            && window.fancybox_remove_update_preview_button) {
          fancybox_remove_update_preview_button($);

          is_in_preview = true;
        }

        if ($('#fancybox-resize').length && window.fancybox_resizing_hide)
          fancybox_resizing_hide();

        if (window.fancybox_add_save_image_button)
          fancybox_add_save_image_button($,zp, is_in_preview,
                                         image_name, image_guid);
      },
      'onComplete': function () {
        var page = zp.template_details.pages[zp.current_page];

        //Define image edit context
        zp.image_edit = {
          'url': {
            'image': zp.url.image,
            'user_image_template': zp.url['user-image-template'] },
          '$selected_thumbnail': $thumb,
           //!!! Temp solution
          '$input': $thumb.parents().children('input.zetaprints-images'),
          'image_id': image_guid,
          'page': {
            'width_in': page['width-in'],
            'height_in': page['height-in']
          },
          'placeholder': page.images[image_name],
          'upload_image_by_url': upload_image_by_url
        };

        //Check if current page has shapes...
        if (page.shapes)
          //...and then add shape info to the image edit context
          zp.image_edit.shape = page.shapes[image_name];

        //Default values for options
        zp.image_edit.has_fit_in_field = true;

        //Add options' values
        if (zp.options['image-edit']) {
          var options = zp.options['image-edit'];

          zp.image_edit.has_fit_in_field = options['in-context']
                              ? options['in-context']['@enabled'] != '0' : true;
        }

        //Disable fit in field functionality if current page doesn't have
        //shapes
        zp.image_edit.has_fit_in_field = zp.image_edit.has_fit_in_field
                                           && zp.image_edit.shape !== undefined;

        zetaprint_image_editor.apply(zp.image_edit,
                                     [ $, { save: save_image_handler } ] );
      },

      'onCleanup' : function () {
        window._zp_image_editor && window._zp_image_editor.close();
      },

      'onClosed': function () {
        if (window.fancybox_remove_save_image_button)
          fancybox_remove_save_image_button($);
      } });
  }

  function export_previews_to_string (details) {
    var previews = '';

    for (number in details.pages) {
      var page = details.pages[number];

      if (page['updated-preview-image'])
        previews += ',' + page['updated-preview-image'].split('preview/')[1];
    }

    return previews.substring(1);
  }

  function add_fake_add_to_cart_button ($original_button,
                                        is_multipage_template) {

    var title = $original_button.attr('title')

    if (is_multipage_template)
      var notice = window.notice_to_update_preview_text_for_multipage_template;
    else
      var notice = window.notice_to_update_preview_text;

    var $fake_button_with_notice = $(
        '<button id="zetaprints-fake-add-to-cart-button"' +
                'class="button disable" type="button"' +
                'title="' + title + '">' +
          '<span><span>' + title + '</span></span>' +
        '</button>' +
        '<span id="zetaprints-fake-add-to-cart-warning"' +
              'class="zetaprints-notice to-update-preview">' +
          notice +
        '</span>' );

    $original_button.addClass('no-display').after($fake_button_with_notice);
  }

  function remove_fake_add_to_cart_button ($original_button) {
    $('#zetaprints-fake-add-to-cart-button, ' +
      '#zetaprints-fake-add-to-cart-warning').remove();
    $original_button.removeClass('no-display');
  }

  function can_show_next_page_button_for_page (page_number, zp) {
    var page = zp.template_details.pages[page_number];

    if (page_number < zp.template_details.pages_number
        && page['updated-preview-image'])
      return true;

    return false;
  }

  function hide_activity () {
    $preview_overlay.addClass('zp-hidden');
  }

  function show_activity () {
    $preview_overlay.removeClass('zp-hidden');
  }

  function enlarge_editor_click_handler () {
    if ($('#fancybox-wrap').is(':visible'))
      $.fancybox.close();
    else
      $('#preview-image-page-' + zp.current_page).click();
  }

  function is_all_pages_updated (details) {
    for (page_number in details.pages)
      if (!details.pages[page_number]['updated-preview-image'])
        return false;

    return true;
  }

  function has_updated_pages (details) {
    for (page_number in details.pages)
      if (details.pages[page_number]['updated-preview-image'])
        return true;

    return false;
  }

  //Set current template page to the first (1-based index)
  this.current_page = 1;

  var $product_form = $('#product_addtocart_form');
  var $product_image_box = $('#zetaprints-preview-image-container').css('position', 'relative');
  var product_image_element = $('#image').parent()[0];
  var has_image_zoomer = $(product_image_element).hasClass('product-image-zoom');

  if (has_changed_fields_on_page(this.current_page))
    $product_form.removeClass('zp-not-modified');
  else
    $product_form.addClass('zp-not-modified');

  var $add_to_cart_button = $('#zetaprints-add-to-cart-button');

  var $form_button = $('#zp-form-button').click(function () {
    var $fields = $('#input-fields-page-' + zp.current_page +
                    ', #stock-images-page-' + zp.current_page);

    zp.is_fields_hidden = !$fields.hasClass('zp-hidden');

    if (zp.is_fields_hidden) {
      $fields.animate({ opacity: 0 }, 500, function () {
        $fields.addClass('zp-hidden');
        $fields.css('opacity', 1);
      });
    } else {
      $fields.css('opacity', 0);
      $fields.removeClass('zp-hidden');
      $fields.animate({ opacity: 1 }, 500);
    }
  })

  var $editor_button = $('#zp-editor-button')
                         .click(enlarge_editor_click_handler);

  var $enlarge_button = $('#zp-enlarge-button')
                          .click(enlarge_editor_click_handler);

  var $update_preview_button = $('#zp-update-preview-form-button');
  var $next_page_button = $('#zp-next-page-button');

  //If base image is not set or it's personalization step (for 2-step theme)
  if (this.is_personalization_step || !has_image_zoomer) {
    $(product_image_element).removeClass('product-image-zoom');

    //then remove all original images placed by M., zoomer and base image
    $(product_image_element)
      .empty();

    //Add preview image placeholder
    var $preview_placeholder = $('<div id="zp-preview-placeholder" />')
                                 .appendTo(product_image_element);

    has_image_zoomer = false;
  }

  var $preview_overlay = $('<div id="zp-preview-overlay" class="zp-no-preview">' +
                             '<div class="zp-preview-overlay-spinner">' +
                               '<div />' +
                             '</div>' +
                             '<div class="zp-preview-overlay-text-wrapper">' +
                               '<span class="zp-preview-overlay-text-left">' +
                                 '&nbsp;' +
                               '</span>' +
                               '<span class="zp-preview-overlay-text-middle">' +
                                 updating_preview_image_text + '&hellip;' +
                               '</span>' +
                               '<span class="zp-preview-overlay-text-right">' +
                                 '&nbsp;' +
                               '</span>' +
                             '</div>' +
                           '</div>')
    .appendTo(product_image_element);

  //Add TemplateID parameter to the form
  $('<input type="hidden" name="zetaprints-TemplateID" value="' +
    this.template_details.guid +'" />').appendTo('#product_addtocart_form');

  //If update_first_preview_on_load parameter was set
  if (this.update_first_preview_on_load)
    //Update preview for the first page
    update_preview({ data: { zp: this } }, true);

  //Create array for preview images sharing links
  if (window.place_preview_image_sharing_link)
    this.preview_sharing_links
                            = new Array(this.template_details.pages_number + 1);

  //Add previews to the product page
  for (var page_number in this.template_details.pages) {
    if (this.template_details.pages[page_number]['updated-preview-url']) {
      var url
            = this.template_details.pages[page_number]['updated-preview-url'];

      if (window.place_preview_image_sharing_link)
        update_preview_sharing_link_for_page(page_number,
                         this.preview_sharing_links, url.split('/preview/')[1]);
    } else
      var url = this.template_details.pages[page_number]['preview-url'];

    $('<a id="preview-image-page-' + page_number + '" ' +
          'class="zetaprints-template-preview zp-hidden" ' +
          'href="' + url + '">' +
        '<img title="' + click_to_view_in_large_size + '" ' +
              'src="' + url + '"' +
              'alt="Preview image for page ' + page_number + '" />' +
      '</a>')
    .appendTo(product_image_element)
    .children()
    .bind('load', {page_number: page_number}, function (event) {
      //Remove preview image placeholder
      if ($preview_placeholder)
        $preview_placeholder.remove();

      $preview_overlay.removeClass('zp-no-preview');

      //Show or hide Next page button for the current page
      if (can_show_next_page_button_for_page(zp.current_page, zp))
        $next_page_button.show();
      else
        $next_page_button.hide();

      //Enable Update preview action
      $update_preview_button.unbind('click');
      $update_preview_button.click({zp: zp}, update_preview);

      var page = zp
                   .template_details
                   .pages[event.data.page_number];

      if (page.preview_is_scaled === undefined) {
        var $_img = $(this)
                      .clone()
                      .css({
                        position: 'absolute',
                        left: '-10000px'
                      })
                      .appendTo('body');

        page.preview_is_scaled = $_img.width() > $product_image_box.width()

        $_img.remove();
      }

      //If no image zoomer on the page and image is for the first page
      //and first page was opened
      if (!has_image_zoomer) {
        if (event.data.page_number == 1 && zp.current_page == 1) {
          //then show preview for the first page
          $('#preview-image-page-1').removeClass('zp-hidden');
        }

        var current_page = zp
                             .template_details
                             .pages[zp.current_page]

        if (event.data.page_number == zp.current_page
            && !current_page.preview_is_scaled)
          $enlarge_button.addClass('zp-hidden');
      }

      hide_activity();
    });
  }

  //Iterate over all image fields in template details...
  for (var page in this.template_details.pages)
    for (var name in this.template_details.pages[page].images)
      //... and if image field has a value then...
      if (this.template_details.pages[page].images[name].value)
        //... mark it as EDITED
        $('#stock-images-page-' + page)
          .children('[title="' + name +'"]')
          .removeClass('no-value');

  if ($.fn.combobox) {
    //Get all dropdown text fields
    var $selects = $('.zetaprints-page-input-fields')
                     .find('select.zetaprints-field');

    //Iterate over all text fields in template details...
    for (var page in this.template_details.pages)
      for (var name in this.template_details.pages[page].fields)
        //... and if text field has combobox flag then...
        if (this.template_details.pages[page].fields[name].combobox)
          //convert relevant DOM element into a combobox
          $selects
            .filter('[name="zetaprints-_' + name + '"]')
            .wrap('<div class="zetaprints-text-field-wrapper" />')
            .combobox();
  }

  $('#page-size-page-1').removeClass('zp-hidden');

  zp.is_fields_hidden = true;

  if (!this.has_shapes || !window.place_all_shapes_for_page) {
    $('#stock-images-page-1, #input-fields-page-1')
      .removeClass('zp-hidden');

    zp.is_fields_hidden = false;

    $editor_button.addClass('zp-hidden');
    $form_button.addClass('zp-hidden');
    $enlarge_button.removeClass('zp-hidden');
  }

  $('div.zetaprints-image-tabs, div.zetaprints-preview-button').css('display', 'block');

  $('div.zetaprints-image-tabs li:first').addClass('selected');

  $('div.tab.user-images').each(function() {
    var tab_button = $('ul.tab-buttons li.hidden', $(this).parents('div.selector-content'));

    if ($('td', this).length > 0)
      $(tab_button).removeClass('hidden');
  });

  $('<input type="hidden" name="zetaprints-previews" value="' +
                      export_previews_to_string(this.template_details) + '" />')
    .appendTo($('#product_addtocart_form'));

  if (is_all_pages_updated(this.template_details)
      || (has_updated_pages(this.template_details)
          && this.template_details.missed_pages == '')
      || this.template_details.missed_pages == 'include')
    $('div.zetaprints-notice.to-update-preview').addClass('zp-hidden');
  else
    add_fake_add_to_cart_button($add_to_cart_button,
                                this.template_details.pages['2'] != undefined);

  //Add resizer for text inputs and text areas for the first page
  if ($.fn.text_field_resizer)
    $('#input-fields-page-1 .zetaprints-text-field-wrapper')
      .text_field_resizer();

  //Set preview images sharing link for the first page
  if (window.place_preview_image_sharing_link)
    set_preview_sharing_link_for_page(1, this.preview_sharing_links);

  $('div.zetaprints-image-tabs li').click({zp: this}, function (event) {
    var zp = event.data.zp;

    $('div.zetaprints-image-tabs li').removeClass('selected');

    //Hide preview image, preview placeholder with spinner, text fields
    //and image fields for the current page
    $('a.zetaprints-template-preview, div.zetaprints-page-stock-images, div.zetaprints-page-input-fields, div.zetaprints-preview-placeholder, .page-size-table-body').addClass('zp-hidden');

    //Remove shapes for current page
    if (zp.has_shapes && window.remove_all_shapes)
      remove_all_shapes($product_image_box);

    $(this).addClass('selected');
    var page = $('img', this).attr('rel');

    //If there's image zoomer on the page
    if (has_image_zoomer) {
      //remove it and base image
      $(product_image_element).removeClass('product-image-zoom');
      $('#image, #track_hint, div.zoom').remove();
      has_image_zoomer = false;
    }

    //Show text fields and image fields for the selected page
    //if it's enabled
    if (!zp.is_fields_hidden)
      $('#stock-images-' + page + ', #input-fields-'+ page)
        .removeClass('zp-hidden');

    //Show preview image, preview placeholder with spinner for the selected page
    $('#preview-image-' + page +
      ', #zp-placeholder-for-preview-' + page +
      ', #page-size-' + page)
      .removeClass('zp-hidden');

    //Add resizer for text inputs and text areas for the selected page
    if ($.fn.text_field_resizer)
      $('#input-fields-' + page + ' .zetaprints-text-field-wrapper')
        .text_field_resizer();

    //Remember number of selected page
    zp.current_page = page.split('-')[1] * 1;

    if (has_changed_fields_on_page(zp.current_page))
      $product_form.removeClass('zp-not-modified');
    else
      $product_form.addClass('zp-not-modified');

    var has_shapes = zp.has_shapes && window.place_all_shapes_for_page;

    var image_box_width = $product_image_box.width();
    var image_width = $('#preview-image-' + page)
                        .children('img')
                        .outerWidth();

    var page = zp
                 .template_details
                 .pages[zp.current_page];

    if (!page.preview_is_scaled || has_shapes)
      $enlarge_button.addClass('zp-hidden');
    else
      //Show Enlarge button
      $enlarge_button.removeClass('zp-hidden');

    //Check if page is static then...
    if (page.static) {
      //... hide Update preview button,
      $update_preview_button.addClass('zp-hidden');

      //Form button
      $form_button.addClass('zp-hidden');

      //and Editor button
      $editor_button.addClass('zp-hidden');
    } else {
      //... otherwise show them
      $update_preview_button.removeClass('zp-hidden');

      //!!! Check if page is passive

      //Check if there's shapes and zpadvanced theme is enabled then...
      if (has_shapes) {
        //... hide Editor button
        $editor_button.removeClass('zp-hidden');

        //Show Form button
        $form_button.removeClass('zp-hidden');
      }
    }

    //Set preview images sharing link for the current page
    if (window.place_preview_image_sharing_link)
      set_preview_sharing_link_for_page(zp.current_page,
                                        zp.preview_sharing_links);

    //Add shapes for selected page
    //if (zp.has_shapes
    //    && window.place_all_shapes_for_page
    //    && window.shape_handler)
    //  place_all_shapes_for_page(
    //                          zp.template_details.pages[zp.current_page].shapes,
    //                          $product_image_box,
    //                          shape_handler);

    if (can_show_next_page_button_for_page(zp.current_page, zp))
      $next_page_button.show();
    else
      $next_page_button.hide();
  });

  if (window.zp_dataset_initialise)
    zp_dataset_initialise(zp);

  function update_preview_sharing_link_for_page (page_number, links, filename) {
    links[page_number] = preview_image_sharing_link_template + filename;
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

  function prepare_string_for_php (s) {
    return s.replace(/\./g, '\x0A');
  }

  function prepare_post_data_for_php (data) {
    var _data = '';

    data = data.split('&');
    for (var i = 0; i < data.length; i++) {
      var token = data[i].split('=');
      _data += '&' + prepare_string_for_php(token[0]) + '=' + token[1];
    }

    return _data.substring(1);
  }

  function prepare_metadata_from_page (page) {
    var metadata = '';

    for (name in page.images) {
      var field_metadata = zp_convert_metadata_to_string(page.images[name]);

      if (!field_metadata)
        continue;

      metadata += '&zetaprints-*#' + prepare_string_for_php(name) + '='
                  + field_metadata + '&';
    }

    for (var name in page.fields) {
      var field_metadata = zp_convert_metadata_to_string(page.fields[name]);

      if (!field_metadata)
        continue;

      metadata += '&zetaprints-*_' + prepare_string_for_php(name) + '='
                  + field_metadata + '&';
    }

    return metadata;
  }

  function serialize_fields_for_page (page_number) {
    return $('#input-fields-page-' + page_number + ', #stock-images-page-'
                                                                  + page_number)
      .find('.zetaprints-field')
      .filter(':text, textarea, :checked, select, [type="hidden"]')
      .serialize();
  }

  var _number_of_failed_updates = 0;

  function update_preview (event, preserve_fields) {
    //Disable click action
    $(this).unbind(event);

    show_activity();

    if ($.fn.text_field_editor)
      $('div.zetaprints-page-input-fields input,' +
        'div.zetaprints-page-input-fields textarea').each(function () {

        $(this).text_field_editor('hide');
      });

    //Convert preserve_field parameter to query parameter
    var preserve_fields = typeof(preserve_fields) != 'undefined'
      && preserve_fields ? '&zetaprints-Preserve=yes' : preserve_fields = '';

    var zp = event.data.zp;

    //!!! Workaround
    //Remember page number
    var current_page = zp.current_page;

    var metadata =
         prepare_metadata_from_page(zp.template_details.pages[zp.current_page]);

    function update_preview_error () {
      if (++_number_of_failed_updates >= 2){
        alert(cannot_update_preview_second_time);

        $('div.zetaprints-notice.to-update-preview').addClass('zp-hidden');
        remove_fake_add_to_cart_button($add_to_cart_button);
        $('div.save-order span').css('display', 'none');
      } else
        alert(cannot_update_preview);

      $update_preview_button.click({zp: zp}, update_preview);

      hide_activity();
    }

    $.ajax({
      url: zp.url.preview,
      type: 'POST',
      dataType: 'json',
      data: prepare_post_data_for_php(serialize_fields_for_page(current_page))
        + '&zetaprints-TemplateID=' + zp.template_details.guid
        + '&zetaprints-From=' + current_page + preserve_fields + metadata,

      error: function (XMLHttpRequest, textStatus, errorThrown) {
        update_preview_error();
      },

      success: function (data, textStatus) {
        if (!data)
          update_preview_error();
        else {
          //!!! Make code in function to not depend on current page number
          //!!! (it's broken way to update preview, user can switch to another
          //!!! page while updating preview)
          //!!! Go throw template details and update previews which has updated
          //!!! preview images (updated-preview-image field)

          //!!! Use updated-preview-image and updated-thumb-image instead
          //!!! updated-preview-url and updated-preview-url
          //!!! Make urls in controller

          var $thumbs = $('div.zetaprints-image-tabs img');

          //Update link to preview image in opened fancybox
          var fancy_img = $('#fancybox-img');
          if (fancy_img.length)
            $(fancy_img).attr('src',
                              data.pages[current_page]['updated-preview-url']);

          for (var page_number in data.pages) {
            var page = zp.template_details.pages[page_number];
            var _page = data.pages[page_number];

            if (_page['updated-preview-image']) {
              page['updated-preview-image'] = _page['updated-preview-image'];
              page['updated-preview-url'] = _page['updated-preview-url'];
            }

            if (_page['updated-thumb-image']) {
              page['updated-thumb-image'] = _page['updated-thumb-image'];
              page['updated-thumb-url'] = _page['updated-thumb-url'];
            }

            var preview_url = data.pages[page_number]['updated-preview-url'];

            if (!preview_url)
              continue;

            //Update links to preview image on current page
            var $preview = $('#preview-image-page-' + page_number);

            $preview.attr('href', preview_url);

            $preview
              .find('img')
              .attr('src', preview_url);

            //Update link to preview thumbnail for current page tab
            $thumbs
              .filter('[rel="page-' + page_number + '"]')
              .attr('src', data.pages[page_number]['updated-thumb-url']);

            var preview = data.pages[page_number]['updated-preview-image'];
            preview = preview.split('preview/')[1];

            //Update preview sharing link if the feature is enabled
            if (window.place_preview_image_sharing_link)
              update_preview_sharing_link_for_page(page_number,
                                    zp.preview_sharing_links, preview);
          }

          //If there's image zoomer on the page
          if (has_image_zoomer) {
            //remove it and base image
            $(product_image_element).removeClass('product-image-zoom');
            $('#image, #track_hint, div.zoom').remove();
            has_image_zoomer = false;
            //and show preview image for the current page
            $('#preview-image-page-1').removeClass('zp-hidden');

            //Add all shapes to personalization form after first preview
            //update
            //if (zp.has_shapes && window.place_all_shapes_for_page
            //    && window.shape_handler)
            //  place_all_shapes_for_page(zp.template_details.pages[1].shapes,
            //                            $product_image_box,
            //                            shape_handler);
          }

          //Show preview sharing link if the feature is enabled
          if (window.place_preview_image_sharing_link)
            set_preview_sharing_link_for_page(current_page,
                                                      zp.preview_sharing_links);

          if (is_all_pages_updated(zp.template_details)
              || zp.template_details.missed_pages == 'include'
              || zp.template_details.missed_pages == '') {

            $('input[name="zetaprints-previews"]')
              .val(export_previews_to_string(zp.template_details));

            $('div.zetaprints-notice.to-update-preview').addClass('zp-hidden');
            remove_fake_add_to_cart_button($add_to_cart_button);
            $('div.save-order span').css('display', 'none');
          }
        }
      }
    });

    return false;
  }

  zp.update_preview = update_preview;

  var upload_controller_url = this.url.upload;
  var image_controller_url = this.url.image;

  $('div.button.choose-file').each(function () {
    var uploader = new AjaxUpload(this, {
      name: 'customer-image',
      action: upload_controller_url,
      responseType: 'json',
      autoSubmit: true,
      onChange: function (file, extension) {
        $(this._button)
          .parents('.upload')
          .find('input.file-name')
          .val(file);
      },
      onSubmit: function (file, extension) {
        $(this._button) //Choose button
          .addClass('disabled')
          .next() //Cancel button
          .addClass('disabled')
          .next() //Spinner
          .show();

        this.disable();
      },
      onComplete: function (file, response) {
        this.enable();

        var $spinner = $(this._button) //Choose button
                         .removeClass('disabled')
                         .next() //Cancel button
                         .addClass('disabled')
                         .next();
                         
        var $upload_div = $spinner.parents('.upload');

        $upload_div
          .find('input.file-name')
          .val('');

        if (response == 'Error') {
          $spinner.hide();

          alert(uploading_image_error_text);

          return;
        }

        var $selector = $upload_div.parents('.selector-content');

        var upload_field_id = $selector.attr('id');

        var trs = $selector.find('.tab.user-images table tr');

        var number_of_loaded_imgs = 0;

        add_image_to_gallery(response.guid, response.thumbnail, function() {
          var $img = $(this);
          var $td = $img.parents('td');

          var field_id = $img
                           .parents('.selector-content')
                           .attr('id');

          //If a field the image was uploaded into is not current image field
          if (field_id != upload_field_id) {
            var $scroll = $td.parents('.images-scroller');

            //Scroll stripper to save position of visible images
            $scroll.scrollLeft($scroll.scrollLeft() + $td.outerWidth());
          } else
            $td
              .children('.zetaprints-images')
              .click();

          if (++number_of_loaded_imgs == trs.length) {
            var $images_div = $upload_div.next();

            $spinner.hide();

            $selector
              .find('> .tab-buttons > .hidden')
              .removeClass('hidden');

            scroll_strip($images_div);

            $selector.tabs('select', 1);
          }
        });
      }
    });

    $('div.button.cancel-upload', $(this).parent()).click(function () {
      if (!$(this).hasClass('disabled')) {
        uploader.cancel();
        uploader.enable();

        $(uploader._button) //Choose button
          .removeClass('disabled')
          .next() //Cancel button
          .addClass('disabled')
          .next() //Spinner
          .hide()
          .parents('.upload')
          .find('input.file-name')
          .val('');
      }
    });
  })

  function image_field_select_handler (event) {
    var $selector = $(event.target).parents('div.zetaprints-images-selector');
    var $content = $selector.parents('.selector-content');

    if (!$selector.get(0)) {
      $content =  $(event.target).parents('.selector-content');
      $selector = $content.data('in-preview-edit').parent;
    }

    var zp = event.data.zp;

    if ($(event.target).val().length) {
      $selector.removeClass('no-value');

      $('#fancybox-outer').addClass('modified');
      $product_form.removeClass('zp-not-modified');

      //If ZetaPrints advanced theme is enabled then...
      if (window.mark_shape_as_edited)
        //... mark shape as edited then image is seleсted
        mark_shape_as_edited(zp.template_details.pages[zp.current_page]
                           .shapes[$(event.target).attr('name').substring(12)]);
    } else {
      $selector.addClass('no-value');

      $('#fancybox-outer').removeClass('modified');

      //If ZetaPrints advanced theme is enabled then...
      if (window.unmark_shape_as_edited)
        //or unmark shape then Leave blank is selected
        unmark_shape_as_edited(zp.template_details.pages[zp.current_page]
                           .shapes[$(event.target).attr('name').substring(12)]);
    }
  }

  zp.show_user_images = function ($panel)  {
    if ($panel.find('input.zetaprints-images').length > 0)
      $panel.tabs('select', 1);
  }

  zp.show_colorpicker = function ($panel) {
    if ($panel.hasClass('color-picker')
        && !$panel.find('input').attr('checked'))
      $panel.find('.color-sample').click();
  }

  function has_changed_fields_on_page (page_number) {
    var $fields = $('#input-fields-page-' + page_number + ', ' +
                   '#stock-images-page-' + page_number);

    if (!$fields.length)
      return false;

    var has_value = false;

    $fields = $fields
                .find('*[name^="zetaprints-_"], *[name^="zetaprints-#"]')
                .filter('textarea, select, :text, :checked')
                .filter('*[type!=hidden]');

    if (!$fields.length)
      return false;

    for (var i = 0; i < $fields.length; i++)
      if ($($fields[i]).val())
        return true;

    return false;
  }

  $(window).load({ zp: this }, function (event) {
    var zp = event.data.zp;

    if (zp.has_shapes
        && window.precalculate_shapes
        /*&& window.place_all_shapes_for_page && shape_handler*/) {

      precalculate_shapes(zp.template_details);

      //Add all shapes only then there's no base image.
      //Shapes will be added after first preview update then base image exists
      //if (!has_image_zoomer)
      //  place_all_shapes_for_page(zp.template_details.pages[zp.current_page].shapes,
      //                            $product_image_box,
      //                            shape_handler);
    }

    if ($.fn.tabs && $.fn.draggable && $.fn.ColorPicker)
      $('.zetaprints-images-selector').each(function () {
        var $field = $(this);

        var $head = $field.children('.head');
        var $content = $field.children('.selector-content');

        var $tabs = $content.children('.tab-buttons');

        var tab_number = 0

        if (!$tabs.children('.hidden').length)
          tab_number = 1;

        $content
          .tabs({ selected: tab_number })
          .bind('tabsshow', function (event, ui) {
            zp.show_colorpicker($(ui.panel));
            scroll_strip(ui.panel);
          });

        $content
          .find('.zetaprints-field')
          .change({ zp: zp }, image_field_select_handler);

        var $panels = $content.find('> .tabs-wrapper > .tab');

        $head.click(function () {
          if ($field.hasClass('minimized')) {
            $field.removeClass('minimized');

            $panel = $panels.not('.ui-tabs-hide');

            zp.show_colorpicker($panel);
            scroll_strip($panel)
          }
          else
            $field
              .addClass('minimized')
              .removeClass('expanded')
              .css('width', '100%');

          return false;
        });

        var shift =
              $field.position().left - $('div.product-img-box').position().left;

        var full_width = shift + $field.outerWidth();

        $head
          .children('.collapse-expand')
          .click(function () {
            $panel = $panels.not('.ui-tabs-hide');

            if ($field.hasClass('expanded'))
              $field
                .removeClass('expanded')
                .removeAttr('style');
            else {
              $field
                .addClass('expanded')
                .css({ 'left': -shift, 'width': full_width });

              if ($field.hasClass('minimized')) {
                $field.removeClass('minimized');

                zp.show_colorpicker($panel);
              }
            }

            scroll_strip($panel);

            return false;
          });

        var $colour_picker_panel = $panels.filter('.color-picker');

        if (!$colour_picker_panel.length)
          return;

        var $colour_radio_button = $colour_picker_panel
                                   .children('.zetaprints-field');

        var $colour_sample = $colour_picker_panel.children('.color-sample')

        var colour = $colour_radio_button.val();

        if (colour)
          $colour_sample.css('backgroundColor', colour);

        $colour_picker_panel
          .find('span > a')
          .click(function () {
            $colour_sample.click();

            return false;
          });

        $colour_sample.ColorPicker({
          color: '#804080',
          onBeforeShow: function (picker) {
            var colour = $colour_radio_button.val();

            if (colour)
              $(this).ColorPickerSetColor(colour);

            $(picker).draggable();
          },
          onSubmit: function (hsb, hex, rgb, picker) {
            $field.removeClass('no-value');
            $colour_sample.css('backgroundColor', '#' + hex);

            $colour_radio_button
              .removeAttr('disabled')
              .val('#' + hex)
              .change()
              .attr('checked', 'checked');

            $(picker).ColorPickerHide();
          }
        });
      });
  });

  $next_page_button.click({zp: this}, function (event) {
    var next_page_number = event.data.zp.current_page + 1;

    $('div.zetaprints-image-tabs li img[rel="page-' + next_page_number +'"]')
      .parent()
      .click();

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
    'onStart' : function () {
      if ($('#zp-select-image-button').length
          && window.fancybox_remove_use_image_button)
        fancybox_remove_use_image_button($);

      if ($('#zp-save-image-button').length
          && window.fancybox_remove_save_image_button)
        fancybox_remove_save_image_button($);

      if (window.fancybox_add_update_preview_button
          && !zp.template_details.pages[zp.current_page].static) {
        fancybox_add_update_preview_button($, zp);
      }
    },
    'onComplete': function () {
      $('img#fancybox-img').attr('title', click_to_close_text);

      //!!! Needs to be implemented via zp object.
      //!!! Page state should be saved in page object.
      if (has_changed_fields_on_page(zp.current_page))
        $('#fancybox-outer').addClass('modified');
      else
        $('#fancybox-outer').removeClass('modified');

      if (window.fancybox_resizing_add)
        fancybox_resizing_add(this);

      if (window.fancybox_update_update_preview_button)
        fancybox_update_update_preview_button($);

      if (!(zp.has_shapes && window.place_all_shapes_for_page
        && window.highlight_shape && window.popup_field_by_name
        && window.fancy_shape_handler))
        return;

      var $fancy_inner = $('div#fancybox-content');

      place_all_shapes_for_page(zp.template_details.pages[zp.current_page].shapes,
                                $fancy_inner, fancy_shape_handler);

      if (zp._shape_to_show) {
        var shape = zp.template_details
                      .pages[zp.current_page]
                      .shapes[zp._shape_to_show];

        zp._shape_to_show = undefined;

        highlight_shape(shape, $fancy_inner);

        popup_field_by_name(shape.name,
                            undefined,
                            shape._fields ? shape._fields : shape.name);
      }
    },
    'onCleanup': function () {
      if (zp.has_shapes && window.popdown_field_by_name) {
        $('div.zetaprints-field-shape', $('div#fancybox-content')).removeClass('highlighted');
        popdown_field_by_name(undefined, true);
      }
    },
    'onClosed': function () {
      if (window.fancybox_remove_update_preview_button)
        fancybox_remove_update_preview_button($);

      if (window.fancybox_resizing_hide)
        fancybox_resizing_hide();
    }
    });

  $('a.in-dialog').fancybox({
    'opacity': true,
    'overlayShow': false,
    'transitionIn': 'elastic',
    'changeSpeed': 200,
    'speedIn': 500,
    'speedOut' : 500,
    'titleShow': false,
    'onStart' : function () {
      var is_in_preview = false;

      if ($('#zp-update-preview-button').length
          && window.fancybox_remove_update_preview_button) {
        fancybox_remove_update_preview_button($);

        is_in_preview = true;
      }

      if ($('#fancybox-resize').length && window.fancybox_resizing_hide)
        fancybox_resizing_hide();

      if (window.fancybox_add_use_image_button)
        fancybox_add_use_image_button($, zp, is_in_preview);
    },
    'onComplete': function () {
      if (window.fancybox_update_preview_button)
        fancybox_update_preview_button($);
    },
    'onClosed': function () {
      if (window.fancybox_remove_use_image_button)
        fancybox_remove_use_image_button($);
    }
  });

  function thumbnail_edit_click_handler () {
    var $target = $(this);
    var $input = $target.parent().children('input');

    show_image_edit_dialog($input.attr('name').substring(12),
                           $input.attr('value'),
                           $target.children('img') );

    return false; 
  }

  $('.image-edit-thumb').click(thumbnail_edit_click_handler);

  if ($.fn.text_field_editor)
    $('.zetaprints-page-input-fields .zetaprints-field')
      .filter(':input:not([type="hidden"])')
      .each(function () {
        var $text_field = $(this);
        var page = $text_field.parents('.zetaprints-page-input-fields')
                     .attr('id')
                     .substring(18);

        var field = zp.template_details.pages[page]
                      .fields[$text_field.attr('name').substring(12)];

        var cached_value = zp_get_metadata(field, 'col-f', '');

        //Remove metadata values, so they won't be used in update preview requests
        //by default
        zp_set_metadata(field, 'col-f', undefined);

        if (field['colour-picker'] != 'RGB')
          return;

        var $button_container = $text_field.parents('dl').children('dt');

        $text_field.text_field_editor({
          button_parent: $button_container,
          colour: cached_value,

          change: function (data) {
            var metadata = {
              'col-f': data.color }

            zp_set_metadata(field, metadata);
          }
        });
      });

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

  function text_fields_change_handle (event) {
    var zp = event.data.zp;

    var $target = $(this);

    if ($target.is(':checkbox'))
      var state = $target.is(':checked');
    else
      var state = $(this).val() != '';

    if (state) {
      $('#fancybox-outer').addClass('modified');
      $product_form.removeClass('zp-not-modified');
    } else
      $('#fancybox-outer').removeClass('modified');

    if (zp.has_shapes
        && window.mark_shape_as_edited
        && window.unmark_shape_as_edited) {

      var shape = get_shape_by_name($target.attr('name').substring(12),
                             zp.template_details.pages[zp.current_page].shapes);

      if (!shape)
        return;

      if (state)
        mark_shape_as_edited(shape);
      else {
        var names = shape.name.split('; ');

        if (names.length != 1) {
          $text_fields = $('#input-fields-page-' + zp.current_page)
                      .find('input, textarea, select')
                      .filter('textarea, select, :text, :checked');

          $image_fields = $('#stock-images-page-' + zp.current_page)
                            .find('input')
                            .filter(':checked');

          for (var i = 0; i < names.length; i++) {
            var name = names[i];

            if ($text_fields.filter('[name="zetaprints-_' + name +'"]').val() ||
                $image_fields.filter('[name="zetaprints-#' + name +'"]').length)
              return;
          }
        }

        unmark_shape_as_edited(shape);
      }
    }

    if (window.zp_dataset_update_state)
      zp_dataset_update_state(zp, $target.attr('name').substring(12), false);
  }

  function readonly_fields_click_handle (event) {
    var name = $(this).attr('name').substring(12);

    if (zp.template_details.pages[zp.current_page].fields[name].dataset)
      $('#zp-dataset-button').click();
    else {
      $(this)
        .unbind(event)
        .val('')
        .removeAttr('readonly');

      //Workaround for IE browser.
      //It moves cursor to the end of input field after focus.
      if (this.createTextRange) {
        var range = this.createTextRange();

        range.collapse(true);
        range.move('character', 0);
        range.select();
      }
    }
  }

  $('div.zetaprints-page-input-fields')
    .find('.zetaprints-field')
    .filter('textarea, :text')
      .keyup({ zp: this }, text_fields_change_handle)
      .filter('[readonly]')
        .click(readonly_fields_click_handle)
      .end()
    .end()
    .filter('select, :checkbox')
      .change({ zp: this }, text_fields_change_handle);

  function delete_image_click_handle (event) {
    event.stopPropagation();

    if (confirm(delete_this_image_text)) {
      var image_id = $(this).parents('td').children('input').attr('value');

      $.ajax({
        url: zp.url.image,
        type: 'POST',
        data: 'zetaprints-action=img-delete&zetaprints-ImageID=' + image_id,
        error: function (request, status, error) {
          alert(cant_delete_text + ': ' + status);
        },
        success: function (data, status) {
          $('input[value="'+ image_id +'"]').parent().remove();
        }
      });
    }

    return false;
  }

  function upload_image_by_url (url) {
    var options = {
      type: 'POST',
      dataType: 'json',
      data: { 'url': url },
      error: function (request, status, error) {
        alert(status + ' ' + error);
      },
      success: function (data, status) {
        console.log(data);
        add_image_to_gallery(data.guid, data.thumbnail_url);

        zp.image_edit.reload_image(data.guid);
      }
    };

    $.ajax(zp.url.upload_by_url, options);
  }

  function add_image_to_gallery (guid, url, on_image_load) {
    var trs = $('.tabs-wrapper > .user-images > table > tbody > tr');

    $(trs).each(function () {
      var $tr = $(this);
      var $template = $tr.children('.zp-html-template');

      var $td = $template
                  .clone()
                  .removeClass('zp-html-template')
                  .insertAfter($template);

      $td
        .children('.zetaprints-field')
        .attr('value', guid)
        .change({ zp: zp }, image_field_select_handler);

      $td
        .children('.image-edit-thumb')
        .click(thumbnail_edit_click_handler);

      var $thumb = $td.children('.image-edit-thumb');

      $thumb
        .find('> .buttons-row > .zp-delete-button')
        .click(delete_image_click_handle);

      var $img = $thumb
                   .children('img')
                   .attr('alt', guid)
                   .attr('src', url);

      if (on_image_load)
        $img.load(on_image_load);
    });
  }

  $('.zp-delete-button').click(delete_image_click_handle);

  $('input.zetaprints-images').click({ zp : this }, function (event) {
    var $input = $(this);
    var field = event.data.zp.template_details
                  .pages[event.data.zp.current_page]
                  .images[$input.attr('name').substring(12)];

    var metadata = $input.data('metadata');

    if (metadata) {
      metadata['img-id'] = $input.attr('value');

      zp_set_metadata(field, metadata);
    } else
      zp_clear_metadata(field);
  });

  if (this.has_shapes && window.add_in_preview_edit_handlers)
    add_in_preview_edit_handlers();
}

jQuery(document).ready(function ($) {
  imageEditorLoadImage();

  var _cropVisualAssistant = new cropVisualAssistant ();

  _cropVisualAssistant.setUserImageThumb(top.userImageThumbSelected);
  _cropVisualAssistant.setTemplatePreview($('a.zetaprints-template-preview:visible>img', top.document).first());

  var $input = top.userImageThumbSelected
              .parents('td')
              .children('input.zetaprints-images');

  var $info_bar = $('div.info-bar');
  var $restore_button = $('#restore-button');

  var crop_data = null;

  var info_bar_elements = {
    'frame': {
      'width': $('#frame-width'),
      'height': $('#frame-height'),
      'dpi': $('#frame-dpi') },

    'uploaded': {
      'width': $('#uploaded-width'),
      'height': $('#uploaded-height'),
      'dpi': $('#uploaded-dpi') },

    'recommended': {
      'width': $('#recommended-width'),
      'height': $('#recommended-height'),
      'dpi': $('#recommended-dpi') } };

  set_info_bar_value('recommended', 'width', _cropVisualAssistant.getPlaceholderInfo().widthPx);
  set_info_bar_value('recommended', 'height', _cropVisualAssistant.getPlaceholderInfo().heightPx);
  set_info_bar_value('recommended', 'dpi', _cropVisualAssistant.getPlaceholderInfo().resolution);

  var image_to_shape_factor = null;

  function imageEditorCrop () {
    set_info_bar_state();

    var cropMetadata = _cropVisualAssistant.getInitCroppedArea(0, 0);

    var container_width = $('#userImagePreviewContainer').width();
    var container_height = $('#userImagePreviewContainer').height();

    var shape_width = _cropVisualAssistant.getPlaceholderInfo().widthPx;
    var shape_height = _cropVisualAssistant.getPlaceholderInfo().heightPx;

    var width_factor = shape_width / container_width;
    var height_factor = shape_height / container_height;

    var factor = width_factor > height_factor ? width_factor : height_factor;

    var selection_width = Math.round(shape_width / factor);
    var selection_height = Math.round(shape_height / factor);

    var image_width = Math.round(_cropVisualAssistant.userImage.widthActualPx / factor);
    var image_height = Math.round(_cropVisualAssistant.userImage.heightActualPx / factor);

    var data = {
      selection: {
        width: selection_width,
        height: selection_height,
        position: {
          top: 0,
          left: 0 } },
      image: {
        width: image_width,
        height: image_height,
        position: {
          top: 0,
          left: 0 } } };

    var metadata = $input.data('metadata');

    if (isCropFit && metadata) {
      var cr_x1 = metadata['cr-x1'];
      var cr_y1 = metadata['cr-y1'];
      var cr_x2 = metadata['cr-x2'];
      var cr_y2 = metadata['cr-y2'];

      if (cr_x1 && cr_y1 && cr_x2 && cr_y2)
        data.selection = {
          width: (cr_x2 - cr_x1) * data.selection.width,
          height: (cr_y2 - cr_y1) * data.selection.height,
          position: {
            top: cr_y1 * data.selection.width,
            left: cr_x1 * data.selection.height } };

      var selection_position = data.selection.position;
      var selection_size = {
        width: data.selection.width,
        height: data.selection.height };

      var sh_x = metadata['sh-x'];
      var sh_y = metadata['sh-y'];

      if (sh_x && sh_y)
        data.image.position = {
          left: selection_position.left + selection_size.width / sh_x,
          top: selection_position.top + selection_size.height / sh_y };

      var sz_x = metadata['sz-x'];
      var sz_y = metadata['sz-y'];

      if (sz_x && sz_y) {
        data.image.width = selection_size.width / sz_x;
        data.image.height = selection_size.height / sz_y;
      }
    }

    $('#userImagePreview').power_crop({
      simple: !isCropFit,
      data: data,
      crop: imageEditorUpdateCropCoords });

    $('#imageEditorCropForm').show();
    $('div.zetaprints-buttons-row').show();
  }

  function imageEditorHideCrop() {
    $('#userImagePreview').power_crop('destroy');

    //$('#imageEditorTooltip').hide();

    $('div.zetaprints-buttons-row').hide();

    $('#imageEditorCropForm').hide();

    //$('#imageEditorImageInfo').empty();
  }

  function imageEditorUpdateCropCoords (data) {
    crop_data = data;

    var c = {
      x: data.selection.position.left,
      y: data.selection.position.top,
      x2: data.selection.position.left + data.selection.width,
      y2: data.selection.position.top + data.selection.height,
      w: data.selection.width,
      h: data.selection.height }

    $('#imageEditorCropX').val(c.x);
    $('#imageEditorCropY').val(c.y);
    $('#imageEditorCropX2').val(c.x2);
    $('#imageEditorCropY2').val(c.y2);
    $('#imageEditorCropW').val(c.w);
    $('#imageEditorCropH').val(c.h);

    if (isCropFit) {
      var image_size = {
        width: data.image.width,
        height: data.image.height };

      var width = Math.round(image_size.width * _cropVisualAssistant.getPlaceholderInfo().widthPx / c.w);
      var height = Math.round(image_size.height * _cropVisualAssistant.getPlaceholderInfo().heightPx / c.h);

      set_info_bar_value('frame', 'width', width);
      set_info_bar_value('frame', 'height', height);

      var thumb_width = _cropVisualAssistant.userImage.widthPreviewPx;

      if (_cropVisualAssistant.getPlaceholderInfo().clipped == true ||
          width > _cropVisualAssistant.templateImage.widthPx ||
          height > _cropVisualAssistant.templateImage.heightPx) {

        var factor = data.selection.width / image_size.width;
        var dpi = Math.round(_cropVisualAssistant.getPlaceholderInfo().resolution * factor);

        if (dpi < _cropVisualAssistant.getPlaceholderInfo().resolution)
          set_info_bar_state('low-resolution-warning');
        else
          set_info_bar_state();

      } else {
        var dpi =  _cropVisualAssistant.getPlaceholderInfo().resolution;
        set_info_bar_state();
      }

      set_info_bar_value('frame', 'dpi', dpi);

      var image_position = data.image.position;

      var mx = Number($('#userImagePreview').width()) / image_size.width;
      var my = Number($('#userImagePreview').height()) / image_size.height;

      var i = {
        x: image_position.left,
        y: image_position.top,
        x2: image_position.left + image_size.width,
        y2: image_position.top + image_size.height };

      if (c.x < i.x)
        c.x = 0;
      else
        c.x = (c.x - image_position.left) * mx;

      if (c.y < i.y)
        c.y = 0;
      else
        c.y = (c.y - image_position.top) * my;

      if (c.x2 > i.x2)
        c.x2 = $('#userImagePreview').width();
      else
        c.x2 = (c.x2 - i.x) * mx;

      if (c.y2 > i.y2)
        c.y2 = $('#userImagePreview').height();
      else
        c.y2 = (c.y2 - i.y) * my;

      _cropVisualAssistant.updateView([c.x, c.y, c.x2, c.y2]);
    } else {
      //updateEditAndSaveInfoBar(c.w, c.h);
      //_cropVisualAssistant.updateInfoBar(c.w, c.h);
    }

    setTimeout(imageEditorAdjustSize, 100);
  }

  /**
   * Perform crop
   */
  function imageEditorApplyCrop () {
    if (isCropFit) {
      storeCropMetadata();
      imageEditorHideCrop();
      parent.jQuery.fancybox.close();
    } else {
      imageEditorHideCrop();
      parent.jQuery.fancybox.showActivity();
      applyCropServer();
    }
  }

  /**
   * Store crop metadata for further usage
   */
  function storeCropMetadata() {
    if (!(isCropFit && crop_data))
      return;

    var width = Number($('#userImagePreview').width());
    var height = Number($('#userImagePreview').height());

    var metadata = {
      'cr-x1': $('#imageEditorCropX').val() / width,
      'cr-x2': $('#imageEditorCropX2').val() / width,
      'cr-y1': $('#imageEditorCropY').val() / height,
      'cr-y2': $('#imageEditorCropY2').val() / height };

    var image_position = crop_data.image.position;
    var selection_position = crop_data.selection.position;

    var image_size = {
      width: crop_data.image.width,
      height: crop_data.image.height };
    var selection_size = {
      width: crop_data.selection.width,
      height: crop_data.selection.height };

    metadata['sh-x'] =
         selection_size.width / (image_position.left - selection_position.left);
    metadata['sh-y'] =
          selection_size.height / (image_position.top - selection_position.top);

    metadata['sz-x'] = selection_size.width / image_size.width;
    metadata['sz-y'] =  selection_size.height / image_size.height;

    $input.data('metadata', metadata);
  }

  function clearCropMetadata () {
    crop_data = null;
    $input.removeData('metadata');

    $('#' + _cropVisualAssistant.getUserImageThumbGuid(), parent.document).each(function(){
      //$(this).data('metadata', null);
      $(this).prev('div.thumbCropedAreaToolSet').remove();
    });
  }

  /**
   * Apply image crop using ZetaPrint server
   */
  function applyCropServer() {
    clearCropMetadata();
    $.ajax({
      url: imageEditorUpdateURL + '?CropX1='+$('#imageEditorCropX').val() + imageEditorDelimeter+'CropY1='+$('#imageEditorCropY').val() + imageEditorDelimeter + 'CropX2=' + $('#imageEditorCropX2').val() + imageEditorDelimeter+'CropY2=' + $('#imageEditorCropY2').val() + imageEditorDelimeter + 'page=img-crop' + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
      type: 'POST',
      data: 'zetaprints-CropX1=' + $('#imageEditorCropX').val() + imageEditorDelimeter + 'zetaprints-CropY1=' + $('#imageEditorCropY').val() + imageEditorDelimeter + 'zetaprints-CropX2=' + $('#imageEditorCropX2').val() + imageEditorDelimeter + 'zetaprints-CropY2=' + $('#imageEditorCropY2').val() + imageEditorDelimeter + 'zetaprints-action=img-crop' + imageEditorDelimeter + 'zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(zetaprints_trans('Can\'t crop image:') + ' ' + textStatus);
      },
      success: function (data, textStatus) {
        imageEditorApplyImage(data);
        //showImageEditorTooltip('Image Cropped');
        $('#userImagePreview').fadeIn();
      }
    });
  }

  /**
   * Perform image restore
   */
  function imageEditorRestore() {
    imageEditorHideCrop();
    clearCropMetadata();
    parent.jQuery.fancybox.showActivity();
    $.ajax({
    url: imageEditorUpdateURL + '?page=img-undo' + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
    type: 'POST',
    data: 'zetaprints-action=img-restore&zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert(zetaprints_trans('Can\'t restore image:') + ' ' + textStatus);
    },
    success: function (data, textStatus) {
      imageEditorApplyImage(data);
      //showImageEditorTooltip('Image Restored');
      $('#userImagePreview').fadeIn();
    }
    });
  }

  /**
   * Initial image load
   */
  function imageEditorLoadImage() {
    parent.jQuery.fancybox.showActivity();
    $.ajax({
      url: imageEditorUpdateURL + '?page=img-props' + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
      type: 'POST',
      datatype: 'XML',
      data: 'zetaprints-action=img&zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(zetaprints_trans('Can\'t load image:') + ' ' + textStatus);
        },
      success: function (data, textStatus) {
        imageEditorApplyImage(data);
        //showImageEditorTooltip('Image Loaded');
      }
    });
  }

  /**
   * Perform image rotate
   */
  function imageEditorDoRotate(dir) {
    imageEditorHideCrop();
    clearCropMetadata();
    parent.jQuery.fancybox.showActivity();
    $.ajax({
      url: imageEditorUpdateURL + '?page=img-rot' + imageEditorDelimeter + 'Rotation=' + dir + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
      type: 'POST',
      data: 'zetaprints-action=img-rotate&zetaprints-Rotation=' + dir + '&zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(zetaprints_trans('Can\'t rotate image:') + ' ' + textStatus);
      },
      success: function (data, textStatus) {
        imageEditorApplyImage(data);
        //showImageEditorTooltip('Image Rotated');
        $('#userImagePreview').fadeIn();
      }
    });
  }

  /**
   * Parse XML output and change image
   */
  function imageEditorApplyImage(xml)
  {
    var userImageWidthActual, userImageHeightActual, uh, uw, src;

    $('#userImagePreview').attr("src", "");

    parent.jQuery.fancybox.showActivity();

    userImageSrc = editor_image_url_template.replace('image-guid.image-ext', getRegexpValue(xml, /Thumb="([^"]*?)"/));
    userImageWidthPreview = getRegexpValue(xml, /ThumbWidth="([^"]*?)"/);
    userImageHeightPreview = getRegexpValue(xml, /ThumbHeight="([^"]*?)"/);
    userImageWidthActual = getRegexpValue(xml, /ImageWidth="([^"]*?)"/);
    userImageHeightActual = getRegexpValue(xml, /ImageHeight="([^"]*?)"/);
    userImageWidthUndo = getRegexpValue(xml, /ImageWidthUndo="([^"]*?)"/);
    userImageHeightUndo = getRegexpValue(xml, /ImageHeightUndo="([^"]*?)"/);

    if (!userImageHeightUndo || !userImageWidthUndo)
      $('#imageEditorRestore').hide();
    else {
      $('#imageEditorRestore').show();
      $('#imageEditorLeftSidebar #imageEditorRestore').attr('title', zetaprints_trans('Undo all changes') + '. ' + zetaprints_trans('Original size') + ': ' + userImageWidthUndo + ' x ' + userImageHeightUndo + ' px.');
    }

    if (!userImageWidthPreview || !userImageHeightPreview) {
      alert(zetaprints_trans('Unknown error occured'));
      return false;
    }

    if (!userImageWidthActual || !userImageHeightActual) {
      alert(zetaprints_trans('Unknown error occured'));
      return false;
    } else {
      _cropVisualAssistant.setUserImage($('#userImagePreview'), userImageWidthActual, userImageHeightActual, userImageWidthPreview, userImageHeightPreview);

      set_info_bar_value('uploaded', 'width', userImageWidthActual);
      set_info_bar_value('uploaded', 'height', userImageHeightActual);
    }

    image_to_shape_factor = _cropVisualAssistant.templateImage.widthPx / userImageWidthActual;

    $('#userImagePreview').attr("src", userImageSrc);
    $('#userImagePreview').width(userImageWidthPreview);
    $('#userImagePreview').height(userImageHeightPreview);

    //updateEditAndSaveInfoBar(userImageWidthPreview, userImageHeightPreview);

    tmp1 = $('input[value=' + imageEditorId + ']', top.document).parent().find('img');
    if (tmp1.length == 0)
      tmp1 = $('#img' + imageEditorId, top.document);
    if (tmp1.length == 0)
      tmp1 = $('input[value=' + imageEditorId + ']', top.document).parent().find('img');
    if (userImageSrc.match(/\.jpg/m))
      tmp1.attr('src', userImageSrc.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));
    else
      tmp1.attr('src', userImageSrc);
  }

  /**
   * Perform image delete
   */
  function imageEditorDelete() {
    if (confirm(zetaprints_trans('Delete this image?'))){
      $.ajax({
        url: imageEditorUpdateURL + '?page=img-del' + imageEditorDelimeter + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
        type: 'POST',
        data: 'zetaprints-action=img-delete&zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(zetaprints_trans('Can\'t delete image:') + ' ' + textStatus);
        },
        success: function (data, textStatus) {
          clearCropMetadata();
          //remove image from strip and close fancybox
          $('input[value='+imageEditorId+']', top.document).parent().remove();
          //also try to remove every element with imageEditorId
          $('#'+imageEditorId, top.document).remove();
          parent.jQuery.fancybox.close();
        }
      });
    }
  }

  /**
   * Adjust fancybox size, place it in center of browser window,
   * and place user image in the center of the fancybox
   */
  function imageEditorAdjustSize()
  {
    var userImagePreviewWidth = 600; //$('#userImagePreview').width();
    var userImagePreviewHeight = 450; //$('#userImagePreview').height();

    var infoBarWidth = $('#imageEditorBottomPanel').width();
    var infoBarHeight = $('#imageEditorBottomPanel').height() + 10;
    // 10 = gap between InfoBar and UserImagePreview plus offset of InfoBar from bottom fancybox edge

    var leftSidebarWidth = 0;
    var leftSidebarHeight = 0;
    if ($('#imageEditorLeftSidebar').length==1) {
      leftSidebarWidth = 100;
      leftSidebarHeight = $('#imageEditorLeftSidebar > ul').height();
    }

    var imageEditorWidth = (infoBarWidth > userImagePreviewWidth) ? infoBarWidth : userImagePreviewWidth;
    imageEditorWidth += leftSidebarWidth;
    imageEditorWidth += 20;

    var imageEditorHeight = userImagePreviewHeight + infoBarHeight;
    imageEditorHeight = (imageEditorHeight > leftSidebarHeight) ? imageEditorHeight : leftSidebarHeight;

    // place UserImage in the center of available area
    var userImagePreviewContainerRight = ((imageEditorWidth - leftSidebarWidth) / 2) - (userImagePreviewWidth / 2);
    var userImagePreviewContainerBottom = infoBarHeight + ((imageEditorHeight - infoBarHeight) / 2) - (userImagePreviewHeight / 2);
    $('#userImagePreviewContainer').css({
      right: userImagePreviewContainerRight,
      bottom: userImagePreviewContainerBottom,
      width: userImagePreviewWidth,
      height: userImagePreviewHeight
    });

    imageEditorHeight += 30;
    // 20 = offset from top edge of fancybox (can be used for top fancybox caption)

    imageEditorWidth = Math.round(imageEditorWidth);
    imageEditorHeight = Math.round(imageEditorHeight);

    $('#fancybox-outer', top.document).width(imageEditorWidth);
    $('#fancybox-outer', top.document).height(imageEditorHeight);
    $('#fancybox-wrap', top.document).width(imageEditorWidth);
    $('#fancybox-wrap', top.document).height(imageEditorHeight);
    $('#fancybox-inner', top.document).width(imageEditorWidth);
    $('#fancybox-inner', top.document).height(imageEditorHeight);

    parent.jQuery.fancybox.center();
  }

  function set_info_message (text) {
   $('#info-message').html(zetaprints_trans(text));
  }

  function set_info_bar_value (type, key, value) {
    info_bar_elements[type][key].html(value);
  }

  function set_info_bar_state (warning) {
    if (warning)
      $info_bar.addClass('warning ' + warning);
    else
      $info_bar.removeClass('warning low-resolution-warning');
  }

  // Check if zetaprints_trans function exists, if not exists create dummy one
  if (!window.zetaprints_trans) {
    function zetaprints_trans (msg) {
      return msg;
    }
  }

  /**
   * Parse regular expression
   */
  function getRegexpValue (subject, exp) {
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

  var isCropFit = true;

  //image load handler. Fade in on load, hide loading icon, show image caption
  $('#userImagePreview').load(function () {
    if (!isCropFit) {
      _cropVisualAssistant.cropedAreaHide();

      parent.jQuery('#fancybox-close').click(function() {
        _cropVisualAssistant.cropedAreaShow();
      });

      //$('#imageEditorImageInfo').empty().append(getEditAndSaveInfoBar());
      //updateEditAndSaveInfoBar(userImageWidthPreview, userImageHeightPreview);
    } else {
      imageEditorCrop();

      //_cropVisualAssistant.getInfoBar().appendTo($('#imageEditorImageInfo'));
      //_cropVisualAssistant.updateInfoBar(userImageWidthPreview, userImageHeightPreview);
    }

    $('#userImagePreview').ready(function () {
      parent.jQuery('#fancybox-loading').fadeOut();
      //$('#imageEditorInfoBar').show();
    });

    imageEditorAdjustSize();
  });

  //button handlers
  $('#imageEditorCrop').click(function() {
    imageEditorHideCrop();
    isCropFit = false;

    $restore_button.addClass('hidden');

    clearCropMetadata();
    imageEditorCrop();
  });
  $('#imageEditorCropFit').click(function(){
    imageEditorHideCrop();
    isCropFit = true;

    $restore_button.removeClass('hidden');

    imageEditorLoadImage();
  });
  $('#save-button').click(imageEditorApplyCrop);

  $('#imageEditorRestore').click(imageEditorRestore);

  $restore_button.click(function () {
    imageEditorHideCrop();
    clearCropMetadata();

    imageEditorLoadImage();
  });

  $('#imageEditorRotateRight').click( function () {
    imageEditorDoRotate('r');
  });
  $('#imageEditorRotateLeft').click( function () {
    imageEditorDoRotate('l');
  });
  $('#imageEditorDelete').click(imageEditorDelete);

  $('div.info-link').click(function () {
    var $bar = $(this).parents('div.info-bar');

    if ($bar.hasClass('opened'))
      $bar.removeClass('opened');
    else
      $bar.addClass('opened');
  });

  $('div.notice-switchers div.icon', $info_bar).hover(
    function () {
      $(this).addClass('hover');

      var $notice_container = $('div.notices div.'
                                + $(this).attr('rel'), $info_bar);

      $notice_container.addClass('notice');
    },

    function () {
      $(this).removeClass('hover');
      $('div.notices div.notice').removeClass('notice');
    }
  );
});

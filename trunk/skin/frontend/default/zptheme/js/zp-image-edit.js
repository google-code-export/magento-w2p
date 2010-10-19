jQuery(document).ready(function ($) {
  var imageEditorJcropApi;
  imageEditorLoadImage();

  var _cropVisualAssistant = new cropVisualAssistant ();

  _cropVisualAssistant.setUserImageThumb(top.userImageThumbSelected);
  _cropVisualAssistant.setTemplatePreview($('a.zetaprints-template-preview:visible>img', top.document).first());

  var _metadataAccessor = new metadataAccessor(
    top.userImageThumbSelected,
    parent.document.getElementById('zetaprints-' + top.image_imageName)
  );

  /**
   * Initialize Jcrop api
   */
  function imageEditorCrop () {
    imageEditorHideCrop();
    showImageEditorTooltip('Select visible part');
    imageEditorJcropApi = $.Jcrop('#userImagePreview', { powerClip: isCropFit });
    imageEditorJcropApi.setOptions( {
      onSelect: imageEditorUpdateCropCoords,
      onChange: imageEditorUpdateCropCoords,
      onImageChange : imageEditorUpdateCropCoords
    });

    //inicialize aspectRatio setting for Jcrop, if not empty data
    if (isCropFit && (top.image_aspectRatio[0] != 0) && (top.image_aspectRatio[1] != 0)) {
      imageEditorJcropApi.setOptions({
        aspectRatio: top.image_aspectRatio[0] / top.image_aspectRatio[1]
      });
    }

    //set an initial selection area
    var cropMetadata = fetchCropMetadata();
    if (cropMetadata[0] && cropMetadata[1] && cropMetadata[2] && cropMetadata[3]) {
      imageEditorJcropApi.setSelect(cropMetadata);
    } else {
      imageEditorJcropApi.setSelect(_cropVisualAssistant.getInitCroppedArea(0, 0));
    }

    if (isCropFit) {
      var selection_position = imageEditorJcropApi.get_selection_position();
      var selection_size = imageEditorJcropApi.get_selection_size();

      imageEditorJcropApi.set_image_position(
        selection_position.left + selection_size.width / _metadataAccessor.getProperty('sh-x'),
        selection_position.top + selection_size.height / _metadataAccessor.getProperty('sh-y') );

      imageEditorJcropApi.set_image_size(
        selection_size.width / _metadataAccessor.getProperty('sz-x'),
        selection_size.height / _metadataAccessor.getProperty('sz-y') );
    }

    $('#imageEditorCropForm').css('display', 'block');
  }

  /**
   * Remove Jcrop, if exists
   */
  function imageEditorHideCrop() {
    $('#imageEditorTooltip').hide();
    $('#imageEditorCropForm').css('display', 'none');
    if (typeof(imageEditorJcropApi) != "undefined")
      imageEditorJcropApi.destroy();

    $('#imageEditorImageInfo').empty();
  }

  /**
   * Jcrop assign box coords
   */
  function imageEditorUpdateCropCoords (c) {
    $('#imageEditorCropX').val(c.x);
    $('#imageEditorCropY').val(c.y);
    $('#imageEditorCropX2').val(c.x2);
    $('#imageEditorCropY2').val(c.y2);
    $('#imageEditorCropW').val(c.w);
    $('#imageEditorCropH').val(c.h);

    if (isCropFit) {
      var image_size = imageEditorJcropApi.get_image_size();

      var width = Math.round(image_size.width * _cropVisualAssistant.getPlaceholderInfo().widthPx / c.w);
      var height = Math.round(image_size.height * _cropVisualAssistant.getPlaceholderInfo().heightPx / c.h);

      updateEditAndSaveInfoBar(width, height);

      _cropVisualAssistant.updateInfoBar(width, height);

      var image_position = imageEditorJcropApi.get_image_position();

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
      updateEditAndSaveInfoBar(c.w, c.h);
      _cropVisualAssistant.updateInfoBar(c.w, c.h);
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
   * Fetch stored crop metadata
   */
  function fetchCropMetadata() {
    var width = Number($('#userImagePreview').width());
    var height = Number($('#userImagePreview').height());

    _metadataAccessor.restoreFromStorage();
    var cropMetadata = [
      Math.round(_metadataAccessor.getProperty('cr-x1') * width),
      Math.round(_metadataAccessor.getProperty('cr-y1') * height),
      Math.round(_metadataAccessor.getProperty('cr-x2') * width),
      Math.ceil(_metadataAccessor.getProperty('cr-y2') * height),

    ];

    return cropMetadata;
  }

  /**
   * Store crop metadata for further usage
   */
  function storeCropMetadata() {
    var width = Number($('#userImagePreview').width());
    var height = Number($('#userImagePreview').height());

    var cr_x1 = $('#imageEditorCropX').val() / width;
    var cr_x2 = $('#imageEditorCropX2').val() / width;
    var cr_y1 = $('#imageEditorCropY').val() / height;
    var cr_y2 = $('#imageEditorCropY2').val() / height;

    if (isCropFit) {
      _metadataAccessor.setProperty('cr-x1', cr_x1);
      _metadataAccessor.setProperty('cr-x2', cr_x2);
      _metadataAccessor.setProperty('cr-y1', cr_y1);
      _metadataAccessor.setProperty('cr-y2', cr_y2);
      _metadataAccessor.setProperty('img-id', imageEditorId);

      var image_position = imageEditorJcropApi.get_image_position();
      var selection_position = imageEditorJcropApi.get_selection_position();

      var image_size = imageEditorJcropApi.get_image_size();
      var selection_size = imageEditorJcropApi.get_selection_size();

      _metadataAccessor.setProperty('sh-x',
        selection_size.width / (image_position.left - selection_position.left) );
      _metadataAccessor.setProperty('sh-y',
        selection_size.height / (image_position.top - selection_position.top) );

      _metadataAccessor.setProperty('sz-x', selection_size.width / image_size.width);
      _metadataAccessor.setProperty('sz-y', selection_size.height / image_size.height);
    }

    _metadataAccessor.storeAll();
  }

  function imageEditorApplyImage() {
    _metadataAccessor.clearAll();
    // _cropVisualAssistant.cropedAreaRemove();

    console.log(_cropVisualAssistant.getUserImageThumbGuid());

    $('#' + _cropVisualAssistant.getUserImageThumbGuid(), parent.document).each(function(){
      $(this).data('metadata', null);
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
        showImageEditorTooltip('Image Cropped');
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
      showImageEditorTooltip('Image Restored');
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
        showImageEditorTooltip('Image Loaded');
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
        showImageEditorTooltip('Image Rotated');
      }
    });
  }

  /**
   * Parse XML output and change image
   */
  function imageEditorApplyImage(xml)
  {
    var userImageWidthPreview, userImageHeightPreview, userImageWidthActual, userImageHeightActual, uh, uw, src;

    $('#userImagePreview').hide();
    $('#userImagePreview').attr("src", "");
    $('#imageEditorInfoBar').hide();
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

    $('#userImagePreview').attr("src", userImageSrc);
    $('#userImagePreview').width(userImageWidthPreview);
    $('#userImagePreview').height(userImageHeightPreview);
    updateEditAndSaveInfoBar(userImageWidthPreview, userImageHeightPreview);

    if (!userImageWidthActual || !userImageHeightActual) {
      alert(zetaprints_trans('Unknown error occured'));
      return false;
    } else {
      _cropVisualAssistant.setUserImage($('#userImagePreview'), userImageWidthActual, userImageHeightActual, userImageWidthPreview, userImageHeightPreview);
    }

    tmp1 = $('input[value=' + imageEditorId + ']', top.document).parent().find('img');
    if (tmp1.length == 0)
      tmp1 = $('#img' + imageEditorId, top.document);
    if (tmp1.length == 0)
      tmp1 = $('input[value=' + imageEditorId + ']', top.document).parent().find('img');
    if (userImageSrc.match(/\.jpg/m))
      tmp1.attr('src', userImageSrc.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));
    else
      tmp1.attr('src', userImageSrc);

    $('#userImagePreview').bind('load', function() {
      if (isCropFit) {
        imageEditorCrop();

        _cropVisualAssistant.getInfoBar().appendTo($('#imageEditorImageInfo'));
        _cropVisualAssistant.updateInfoBar(userImageWidthPreview, userImageHeightPreview);
      } else {
        $('#imageEditorImageInfo').empty().append(getEditAndSaveInfoBar());
        updateEditAndSaveInfoBar(userImageWidthPreview, userImageHeightPreview);
      }
      imageEditorAdjustSize();
    });
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
   * Update InfoBar
   */
  function updateEditAndSaveInfoBar(_width, _height)
  {
    $('#imageEditorWidthInfo').html(_width + ' px');
    $('#imageEditorHeightInfo').html(_height + ' px');
  }

  /**
   * Get InfoBar for "Edit And Save" fancybox
   */
  function getEditAndSaveInfoBar()
  {
    return $(
      '<TABLE id="infoBar"><TR>' +
      '<TD>' + zetaprints_trans('W:') + '</TD><TD id="imageEditorWidthInfo"></TD>' +
      '<TD>' + zetaprints_trans('H:') + '</TD><TD id="imageEditorHeightInfo"></TD>' +
      '</TR></TABLE>');
  }

  /**
   * Show messages in the InfoBar imageEditorTooltip
   */
  function showImageEditorTooltip(_message)
  {
    $('#imageEditorInfoBar').show();
    // $('#imageEditorCropForm').css('display', 'block');

    $('#imageEditorTooltip').html(zetaprints_trans(_message));
    $('#imageEditorTooltip').show('fast', function () {
      imageEditorAdjustSize();
    });
  }

  /**
   * Adjust fancybox size, place it in center of browser window,
   * and place user image in the center of the fancybox
   */
  function imageEditorAdjustSize()
  {
    var userImagePreviewWidth = $('#userImagePreview').width();
    var userImagePreviewHeight = $('#userImagePreview').height();

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
    imageEditorWidth += 10;

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

    imageEditorHeight += 20;
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
    // _cropVisualAssistant.cropedAreaRemove();
    if (!isCropFit) {
      _cropVisualAssistant.cropedAreaHide();

      parent.jQuery('#fancybox-close').click(function() {
        _cropVisualAssistant.cropedAreaShow();
      });
    }

    $('#userImagePreview').fadeIn().ready(function () {
      parent.jQuery('#fancybox-loading').fadeOut();
      $('#imageEditorInfoBar').show();
    });
  });

  //button handlers
  $('#imageEditorCrop').click(function() {
    isCropFit = false;
    clearCropMetadata();
    imageEditorCrop();
  });
  $('#imageEditorCropFit').click(function(){
    isCropFit = true;
    imageEditorLoadImage();
    imageEditorCrop();
  });
  $('#imageEditorApplyCrop').click(imageEditorApplyCrop);

  $('#imageEditorRestore').click(imageEditorRestore);
  $('#imageEditorRotateRight').click( function () {
    imageEditorDoRotate('r');
  });
  $('#imageEditorRotateLeft').click( function () {
    imageEditorDoRotate('l');
  });
  $('#imageEditorDelete').click(imageEditorDelete);
});

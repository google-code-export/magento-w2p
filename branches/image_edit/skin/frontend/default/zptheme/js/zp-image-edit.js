jQuery(document).ready(function ($) {
  var imageEditorJcropApi;
  imageEditorLoadImage();

  //inicialize Jcrop api
  function imageEditorCrop () {
    imageEditorHideCrop();
    imageEditorInfoBox('Use triggers to crop image');
    imageEditorJcropApi = $.Jcrop('#imageEditorRight #imageEditorPreview');
    imageEditorJcropApi.setOptions( {
      onSelect: imageEditorUpdateCropCoords,
      onChange: imageEditorUpdateCropCoords
    });

    //inicialize aspectRatio setting for Jcrop, if not empty data
    if ((top.image_aspectRatio[0] != 0) && (top.image_aspectRatio[1] != 0)) {
      imageEditorJcropApi.setOptions({
        aspectRatio: top.image_aspectRatio[0] / top.image_aspectRatio[1]
      });
    }

    //set an initial selection area
    var cropMetadata = fetchCropMetadata();
    if (cropMetadata[0] && cropMetadata[1] && cropMetadata[2] && cropMetadata[3]) {
      imageEditorJcropApi.setSelect(cropMetadata);
    } else {
      imageEditorJcropApi.setSelect([10, 10, 60, 60]);
    }

    $('#imageEditorCropForm').css('display', 'block');
  }

  //remove Jcrop, if exists
  function imageEditorHideCrop () {
    $('#imageEditorInfo').hide();
    $('#imageEditorCropForm').css('display', 'none');
    if (typeof(imageEditorJcropApi) != "undefined")
      imageEditorJcropApi.destroy();
  }

  /**
   * Remove cropped area from the thumbnail
   * @attr: _targetImageElement - affected thumbnail image
   */
  function thumbCropedAreaRemove (_targetImageElement) {
    _targetImageElement.prev('div.thumbCropedAreaToolSet').remove();
  }

  /**
   * Set cropped area in the thumbnail
   * @attr: _targetImageElement - affected thumbnail image
   * @attr: _cropArea - array of [cr_x1, cr_y1, cr_x2, cr_y2]
   */
  function thumbCropedAreaSet (_targetImageElement, _cropArea) {
    thumbCropedAreaRemove (_targetImageElement)

    
    // alert(_targetImageElement.attr("id"));
    // alert(_targetImageElement.position().left + ', ' + _targetImageElement.position().top)
    // alert(_targetImageElement.width() + ', ' + _targetImageElement.height())
  	
  	if (_cropArea[0]==_cropArea[2] || _cropArea[1]==_cropArea[3])
  		return;

    var _targetImageElementPos = _targetImageElement.position();
    var _toolSet = jQuery('<DIV />');
    _toolSet.css({
      position: 'absolute',
      left: _targetImageElementPos.left,
      top: _targetImageElementPos.top,
      width: _targetImageElement.width(),
      height: _targetImageElement.height(),
      backgroundColor: 'black',
      opacity: '0.7'
    }).attr('class', 'thumbCropedAreaToolSet').insertBefore(_targetImageElement);

    var _cropAreaLeft = Math.round(_targetImageElement.width() * _cropArea[0]);
    var _cropAreaTop = Math.round(_targetImageElement.height() * _cropArea[1]);

    var _cropAreaDiv = jQuery('<DIV />');
    _cropAreaDiv.css({
      position: 'absolute',
      left: _cropAreaLeft,
      top: _cropAreaTop,
      width: Math.round(_targetImageElement.width() * _cropArea[2]) - _cropAreaLeft,
      height: Math.round(_targetImageElement.height() * _cropArea[3]) - _cropAreaTop,
      overflow: 'hidden'
    }).appendTo(_toolSet);

    jQuery('<IMG src="' + _targetImageElement.attr("src") + '" />').css({
      position: 'absolute',
      left: -_cropAreaLeft,
      top: -_cropAreaTop
    }).appendTo(_cropAreaDiv);
  }

  //Jcrop assign box coords
  function imageEditorUpdateCropCoords (c) {
    $('#imageEditorCropX').val(c.x);
    $('#imageEditorCropY').val(c.y);
    $('#imageEditorCropX2').val(c.x2);
    $('#imageEditorCropY2').val(c.y2);
    $('#imageEditorCropW').val(c.w);
    $('#imageEditorCropH').val(c.h);
    $('#imageEditorHeightInfo').html(c.h + ' px');
    $('#imageEditorWidthInfo').html(c.w + ' px');

    var width = Number($('#imageEditorRight #imageEditorPreview').width());
    var height = Number($('#imageEditorRight #imageEditorPreview').height());

    // thumbCropedAreaRemove (top.userImageThumbSelected[0]);
    thumbCropedAreaSet (top.userImageThumbSelected, [c.x/width, c.y/height, c.x2/width, c.y2/height]);
  }

  //perform crop
  function imageEditorApplyCrop () {
    imageEditorHideCrop();
    if (isCropFit) {
      storeCropMetadata();
      parent.jQuery.fancybox.close();
    } else {
      parent.jQuery.fancybox.showActivity();
      applyCropServer();
    }    
  }

  /**
   * Class for storing any number of keys and values in specified html input element
   * @attr: _storageInputElement - html input element
   */
  function metadataAccessor (_storageInputElement) {
    this.metadataAccessor = function(_storageInputElement) {
      this._storageInputElement = _storageInputElement
    }
    this.metadataAccessor(_storageInputElement)

    this.restoreFromStorage = function() {
      var _metadata = top.userImageThumbSelected.data('metadata');
      var _key_val_pairs = (_metadata==null) ? [] : _metadata.split(';');
      // var _key_val_pairs = this._storageInputElement.value.split(';');
      for (var _i in _key_val_pairs) {
        [_key, _val] = _key_val_pairs[_i].split('=');
        this.setProperty(_key, _val);
      }
    }

    this.storeAll = function() {
      var _outArr = [];
      var _j = 0;
      for(var _i in this) if (typeof(this[_i])!='function' && _i != '_storageInputElement') {
        _outArr[_j++] = _i + '=' + this[_i];
      }
      var _metadata = _outArr.join(';');
      top.userImageThumbSelected.data('metadata', _metadata);
      if ($('input[name=zetaprints-#' + top.image_imageName + ']:checked', top.document).val()==top.userImageThumbSelected.attr('id'))
        this._storageInputElement.value = _metadata;
    }

    this.setProperty = function(_propertyName, _propertyValue) {
      this[_propertyName] = _propertyValue;
    }

    this.getProperty = function(_propertyName) {
      if(typeof(this[_propertyName])=='undefined')
        return null
      else
        return this[_propertyName];
    }
  }

  /**
   * Fetch stored crop metadata
   */
  function fetchCropMetadata() {
  	var width = Number($('#imageEditorRight #imageEditorPreview').width());
    var height = Number($('#imageEditorRight #imageEditorPreview').height());

    var ma = new metadataAccessor(parent.document.getElementById('zetaprints-' + top.image_imageName));
    ma.restoreFromStorage();
    var cropMetadata = [Math.round(ma.getProperty('cr-x1') * width), Math.round(ma.getProperty('cr-y1') * height), Math.round(ma.getProperty('cr-x2') * width), Math.ceil(ma.getProperty('cr-y2') * height)];

    return cropMetadata;
  }
  
  /**
   * Store crop metadata for further usage
   */
  function storeCropMetadata() {
    var width = Number($('#imageEditorRight #imageEditorPreview').width());
    var height = Number($('#imageEditorRight #imageEditorPreview').height());

    var cr_x1 = $('#imageEditorCropX').val() / width;
    var cr_x2 = $('#imageEditorCropX2').val() / width;
    var cr_y1 = $('#imageEditorCropY').val() / height;
    var cr_y2 = $('#imageEditorCropY2').val() / height;

    // thumbCropedAreaRemove (top.userImageThumbSelected[0]);
    // thumbCropedAreaSet (top.userImageThumbSelected[0], [cr_x1, cr_y1, cr_x2, cr_y2]);

    var ma = new metadataAccessor(parent.document.getElementById('zetaprints-' + top.image_imageName));
    ma.setProperty('cr-x1', cr_x1);
    ma.setProperty('cr-x2', cr_x2);
    ma.setProperty('cr-y1', cr_y1);
    ma.setProperty('cr-y2', cr_y2);
    ma.setProperty('img-id', imageEditorId);
    ma.storeAll();
  }
  
  
  /**
   * Apply image crop using ZetaPrint server
   */
  function applyCropServer() {
    $.ajax({
      url: imageEditorUpdateURL + '?CropX1='+$('#imageEditorCropX').val() + imageEditorDelimeter+'CropY1='+$('#imageEditorCropY').val() + imageEditorDelimeter + 'CropX2=' + $('#imageEditorCropX2').val() + imageEditorDelimeter+'CropY2=' + $('#imageEditorCropY2').val() + imageEditorDelimeter + 'page=img-crop' + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
      type: 'POST',
      data: 'zetaprints-CropX1=' + $('#imageEditorCropX').val() + imageEditorDelimeter + 'zetaprints-CropY1=' + $('#imageEditorCropY').val() + imageEditorDelimeter + 'zetaprints-CropX2=' + $('#imageEditorCropX2').val() + imageEditorDelimeter + 'zetaprints-CropY2=' + $('#imageEditorCropY2').val() + imageEditorDelimeter + 'zetaprints-action=img-crop' + imageEditorDelimeter + 'zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        alert(zetaprints_trans('Can\'t crop image:') + ' ' + textStatus);
      },
      success: function (data, textStatus) {
      imageEditorApplyImage(data);
        imageEditorInfoBox('Image Cropped');
      }
    });
  }

  /**
   * Perform image restore
   */
  function imageEditorRestore () {
    imageEditorHideCrop();
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
      imageEditorInfoBox('Image Restored');
    }
    });
  }

  //initial image load
  function imageEditorLoadImage () {
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
        imageEditorInfoBox('Image Loaded');
      }
    });
  }

  //perform image rotate
  function imageEditorDoRotate (dir) {
    imageEditorHideCrop();
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
        imageEditorInfoBox('Image Rotated');
      }
    });
  }

  //parse xml output and change image
  function imageEditorApplyImage (xml) {
    var h, w, uh, uw, src;
    $('#imageEditorPreview').hide();
    $('#imageEditorPreview').attr("src", "");
    $('#imageEditorCaption').hide();
    parent.jQuery.fancybox.showActivity();

    src = editor_image_url_template.replace('image-guid.image-ext', getRegexpValue(xml, /Thumb="([^"]*?)"/));
    h=getRegexpValue(xml, /ThumbHeight="([^"]*?)"/);
    w=getRegexpValue(xml, /ThumbWidth="([^"]*?)"/);
    uh=getRegexpValue(xml, /ImageHeightUndo="([^"]*?)"/);
    uw=getRegexpValue(xml, /ImageWidthUndo="([^"]*?)"/);
    if (!uh || !uw)
      $('#imageEditorRestore').hide();
    else {
      $('#imageEditorRestore').show();
      $('#imageEditorLeft #imageEditorRestore').attr('title', zetaprints_trans('Undo all changes') + '. ' + zetaprints_trans('Original size') + ': ' + uw + ' x ' + uh + ' px.');
    }
    if (!h || !w) {
      alert(zetaprints_trans('Unknown error occured'));
      return false;
    }
    $('#imageEditorPreview').attr("src", src);
    $('#imageEditorPreview').height(h);
    $('#imageEditorPreview').width(w);
    $('#imageEditorHeightInfo').html(h + ' px');
    $('#imageEditorWidthInfo').html(w + ' px');

    tmp1 = $('input[value='+imageEditorId+']', top.document).parent().find('img');
    if (tmp1.length == 0)
      tmp1 = $('#img'+imageEditorId, top.document);
    if (tmp1.length == 0)
      tmp1 = $('input[value='+imageEditorId+']', top.document).parent().find('img');
    if (src.match(/\.jpg/m))
      tmp1.attr('src', src.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));
    else
      tmp1.attr('src', src);
    imageEditorApplySize(w, h);
  }

  //perform image delete
  function imageEditorDelete (){
    if (confirm(zetaprints_trans('Delete this image?'))){
      $.ajax({
        url: imageEditorUpdateURL + '?page=img-del' + imageEditorDelimeter + imageEditorDelimeter + 'ImageID=' + imageEditorId + imageEditorQueryAppend,
        type: 'POST',
        data: 'zetaprints-action=img-delete&zetaprints-ImageID=' + imageEditorId + imageEditorQueryAppend,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(zetaprints_trans('Can\'t delete image:') + ' ' + textStatus);
        },
        success: function (data, textStatus) {
          //remove image from strip and close fancybox
          $('input[value='+imageEditorId+']', top.document).parent().remove();
          //also try to remove every element with imageEditorId
          $('#'+imageEditorId, top.document).remove();
          parent.jQuery.fancybox.close();
        }
      });
    }
  }

  //show info text on blue line below
  function imageEditorInfoBox (msg) {
    $('#imageEditorCaption').show();
    if ($.browser.msie)
      $('#imageEditorCaption').width($('#imageEditorRight #imageEditorPreview').width());
    else
      $('#imageEditorCaption').width($('#imageEditorRight #imageEditorPreview').width() - 10);
    $('#imageEditorInfo').html(zetaprints_trans(msg));
    $('#imageEditorInfo').show('fast', function () {
      var cw = 0;
      $('#imageEditorCaption span').each( function () {
        cw += $(this).width();
      });
      if (cw < 280)
        cw = 280;
      if ($('#imageEditorRight #imageEditorPreview').width() < cw) {
        $('#imageEditorCaption').width(cw);
        imageEditorApplySize(cw, $('#imageEditorRight #imageEditorPreview').height());
      }
    });
  }

  //change fancybox size and center it
  function imageEditorApplySize (w, h) {
    //min dimensions
    if (w < 300 || typeof(w) == "undefined")
      w = 300;
    if (h < 300 || typeof(h) == "undefined")
      h = 300;
    $('#fancybox-outer', top.document).width(Number(w) + 120);
    $('#fancybox-outer', top.document).height(Number(h) + 75);
    $('#fancybox-wrap', top.document).width(Number(w) + 120);
    $('#fancybox-wrap', top.document).height(Number(h) + 75);
    $('#fancybox-inner', top.document).width(Number(w) + 120);
    $('#fancybox-inner', top.document).height(Number(h) + 75);
    parent.jQuery.fancybox.center();
  }

  //check if zetaprints_trans function exists, if not exists create dummy one
  if (!window.zetaprints_trans) {
    function zetaprints_trans (msg) {
      return msg;
    }
  }

  //parse regular expression
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

  //image load handler. Fade in on load, hide loading icon, show image caption
  $('#imageEditorPreview').load(function(){
    $('#imageEditorPreview').fadeIn().ready( function () {
      parent.jQuery('#fancybox-loading').fadeOut();
      $('#imageEditorCaption').show();
    });
  });

  //button handlers
  var isCropFit = false;
  $('#imageEditorCrop').click(function() {
    isCropFit = false;
    imageEditorCrop();
  });
  $('#imageEditorCropFit').click(function(){
    isCropFit = true;
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

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
    imageEditorJcropApi.setSelect([Number($('#imageEditorRight #imageEditorPreview').width())*Number(0.9), Number($('#imageEditorRight #imageEditorPreview').height())*Number(0.9), Number($('#imageEditorRight #imageEditorPreview').width())*Number(0.1), Number($('#imageEditorRight #imageEditorPreview').height())*Number(0.1)]);
    $('#imageEditorCropForm').css('display', 'block');
  }

  //remove Jcrop, if exists
  function imageEditorHideCrop () {
    $('#imageEditorInfo').hide();
    $('#imageEditorCropForm').css('display', 'none');
    if (typeof(imageEditorJcropApi) != "undefined")
      imageEditorJcropApi.destroy();
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
  }

  //perform crop
  function imageEditorApplyCrop () {
    imageEditorHideCrop();
    parent.jQuery.fancybox.showActivity();
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

  //perform image restore
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

    src = editor_image_url_template.replace('image-guid.image-ext',
                                      getRegexpValue(xml, /Thumb="([^"]*?)"/));

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
  $('#imageEditorCrop').click(imageEditorCrop);
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

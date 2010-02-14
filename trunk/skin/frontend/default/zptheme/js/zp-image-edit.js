jQuery(document).ready(function($)
  {
    var jcrop_api;
    parent.document.getElementById('fancy_frame').style.overflow = 'hidden';
    loadimg();
    function hide_resize() {
      $('#resize_form').css('display', 'none');
      $("#edit").resizable('destroy');
    }
    function crop()
    {
      hide_crop();
      jcrop_api = $.Jcrop('#edit');
      jcrop_api.setOptions({onSelect: updatecropCoords, onChange: updatecropCoords});
      jcrop_api.setSelect([Number($('#edit').width())*Number(0.9), Number($('#edit').height())*Number(0.9), Number($('#edit').width())*Number(0.1), Number($('#edit').height())*Number(0.1)]);
      $('#crop_form').css('display', 'block');
    };


    function hide_crop() {
      $('#crop_form').css('display', 'none');
      if (typeof(jcrop_api) != "undefined") {
        jcrop_api.destroy();
      }
    }
    function updatecropCoords(c)
    {
      $('#cropx').val(c.x);
      $('#cropy').val(c.y);
      $('#cropx2').val(c.x2);
      $('#cropy2').val(c.y2);
      $('#cropw').val(c.w);
      $('#croph').val(c.h);

    };

    function apply_crop() {
      loader();
      $.ajax(
        {
        url: update_url,
        type: 'POST',
        data: $('#crop_form').serialize()+'&zetaprints-action=img-crop&zetaprints-ImageID='+image_id,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Can\'t crop image: ' + textStatus);},
        success: function (data, textStatus) {
            apply_img(data);
            hide_resize();
            //$.Jcrop('#edit').destroy();
            $('#crop_form').css('display', 'none');
            jcrop_api.destroy();
          }
        }
      );
    }

    function restore() {
      hide_crop();
      loader();
      $.ajax(
        {
        url: update_url,
        type: 'POST',
        data: 'zetaprints-action=img-restore&zetaprints-ImageID='+image_id,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Can\'t restore image: ' + textStatus);},
        success: function (data, textStatus) {
            apply_img(data);
          }
        }
      );
      //reset size
    }
    function loadimg() {
      loader();
      $.ajax(
        {
        url: update_url,
        type: 'POST',
        datatype: 'XML',
        data: 'zetaprints-action=img&zetaprints-ImageID='+image_id,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Can\'t load image: ' + textStatus);},
        success: function (data, textStatus) {
            apply_img(data);
          }
        }
      );
    }
    function dorotate(dir) {
      hide_crop();
      loader();
      $.ajax(
        {
        url: update_url,
        type: 'POST',
        data: 'zetaprints-action=img-rotate&zetaprints-Rotation='+dir+'&zetaprints-ImageID='+image_id,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Can\'t rotate image: ' + textStatus);},
        success: function (data, textStatus) {
            apply_img(data);
          }
        }
      );
    }
    function apply_img(xml) {
      var h, w;
      $('#edit').hide();
      if(! window.DOMParser)
      {
        var xmlDoc = null;
        xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = false;
        xmlDoc.loadXML(xml);
        var t = $(xmlDoc);
        t.find('Image').each(function()
          {


            $('#edit').attr("src", 'http://magento.zetaprints.com/photothumbs/'+$(this).attr('Thumb'));
            document.getElementById('edit').style.height = $(this).attr('ThumbHeight')+'px';
            document.getElementById('edit').style.width = $(this).attr('ThumbWidth')+'px';

            h = $(this).attr('ThumbHeight');
            w = $(this).attr('ThumbWidth');
          }
        );
      }
      else {
        var t = $(xml);
        t.find('img').each(function()
          {


            $('#edit').attr("src", 'http://magento.zetaprints.com/photothumbs/'+$(this).attr('thumb'));


            document.getElementById('edit').style.height = $(this).attr('thumbheight')+'px';
            document.getElementById('edit').style.width = $(this).attr('thumbwidth')+'px';
            h = $(this).attr('thumbheight');
            w = $(this).attr('thumbwidth');


          }
        );
      }

      $('#edit')
      .load(
        function() {
          $('#edit').fadeIn();
        }
      );

      parent.document.getElementById('fancy_outer').style.height = Number(h)+Number(45)+'px';
      parent.document.getElementById('fancy_outer').style.width = Number(w)+Number(120)+'px';

      parent.jQuery.fn.fancybox.scrollBox();
      parent.document.getElementById('fancy_loading').style.display = 'none';
    }


    function loader() {
      parent.jQuery.fn.fancybox.showLoading();
    }
    $('#crop').click(crop);
    $('#apply_crop').click(apply_crop);
    $('#restore').click(restore);
    $('#rotate').click(function() {dorotate('r');});
    $('#rotatel').click(function() {dorotate('l');});
  }
);

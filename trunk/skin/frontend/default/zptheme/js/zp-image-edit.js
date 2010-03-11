jQuery(document).ready(function($)
    {
        var jcrop_api;
        parent.document.getElementById('fancy_frame').style.overflow = 'hidden';
        loadimg();

        function crop()
        {
            hide_crop();
            infobox('Use triggers to crop image');
            jcrop_api = $.Jcrop('#picedit_right #edit');
            jcrop_api.setOptions(
            {
                onSelect: updatecropCoords,
                onChange: updatecropCoords
            }
            );
            jcrop_api.setSelect([Number($('#picedit_right #edit').width())*Number(0.9), Number($('#picedit_right #edit').height())*Number(0.9), Number($('#picedit_right #edit').width())*Number(0.1), Number($('#picedit_right #edit').height())*Number(0.1)]);
            $('#crop_form').css('display', 'block');
        }


        function hide_crop() {
            $('#image-edit-info').hide();
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
            $('#image-edit-info-height').html(c.h+' px');
            $('#image-edit-info-width').html(c.w+' px');

        }

        function apply_crop() {
            hide_crop();
            loader();
            $.ajax(
            {
                url: im_update_url+'?'+$('#crop_form').serialize()+im_delimeter+'zetaprints-action=img-crop'+im_delimeter+'zetaprints-ImageID='+im_image_id+im_append,
                type: 'POST',
                data: $('#crop_form').serialize()+'&zetaprints-action=img-crop&zetaprints-ImageID='+im_image_id+im_append,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t crop image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    apply_img(data);
                    infobox('Image Cropped');
                }
            }
            );
        }

        function restore() {
            hide_crop();
            loader();
            $.ajax(
            {
                url: im_update_url+'?zetaprints-action=img-restore'+im_delimeter+'zetaprints-ImageID='+im_image_id+im_append,
                type: 'POST',
                data: 'zetaprints-action=img-restore&zetaprints-ImageID='+im_image_id+im_append,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t restore image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    apply_img(data);
                    infobox('Image Restored');
                }
            }
            );
        }
        function loadimg() {
            loader();
            $.ajax(
            {
                url: im_update_url+'?zetaprints-action=img'+im_delimeter+'zetaprints-ImageID='+im_image_id+im_append,
                type: 'POST',
                datatype: 'XML',
                data: 'zetaprints-action=img&zetaprints-ImageID='+im_image_id+im_append,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t load image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    apply_img(data);
                    infobox('Image Loaded');
                }
            }
            );
        }
        function dorotate(dir) {
            hide_crop();
            loader();
            $.ajax(
            {
                url: im_update_url+'?zetaprints-action=img-rotate'+im_delimeter+'zetaprints-Rotation='+dir+im_delimeter+'zetaprints-ImageID='+im_image_id+im_append,
                type: 'POST',
                data: 'zetaprints-action=img-rotate&zetaprints-Rotation='+dir+'&zetaprints-ImageID='+im_image_id+im_append,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t rotate image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    apply_img(data);
                    infobox('Image Rotated');
                }
            }
            );
        }
        function apply_img(xml) {
            var h, w;
            $('#picedit_right #edit').hide();
            $('#image-edit-caption').hide();
            loader();
            if(! window.DOMParser)
            {
                var xmlDoc = null;
                xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
                xmlDoc.async = false;
                xmlDoc.loadXML(xml);
                var t = $(xmlDoc);
                t.find('Image').each(function()
                {

                    $('#picedit_right #edit').attr("src", im_zp_url+'/photothumbs/'+$(this).attr('Thumb'));

                    $('#picedit_right #edit').height($(this).attr('ThumbHeight'));
                    $('#picedit_right #edit').width($(this).attr('ThumbWidth'));
                    $('#image-edit-info-height').html($(this).attr('ImageHeight')+' px');
                    $('#image-edit-info-width').html($(this).attr('ImageWidth')+' px');

                    h = $(this).attr('ThumbHeight');
                    w = $(this).attr('ThumbWidth');

                }
                );
            }
            else {
                t = $(xml);
                t.find('img').each(function()
                {

                    $('#picedit_right #edit').attr("src", im_zp_url+'/photothumbs/'+$(this).attr('thumb'));

                    $('#picedit_right #edit').height($(this).attr('thumbheight'));
                    $('#picedit_right #edit').width($(this).attr('thumbwidth'));
                    $('#image-edit-info-height').html($(this).attr('imageheight')+' px');
                    $('#image-edit-info-width').html($(this).attr('imagewidth')+' px');

                    h = $(this).attr('thumbheight');
                    w = $(this).attr('thumbwidth');

                }
                );
            }

            $('#picedit_right #edit')
            .load(
                function() {
                    $('#picedit_right #edit').fadeIn().ready(function ()
                    {
                        parent.jQuery('#fancy_loading').fadeOut();
                        $('#image-edit-caption').show();
                    }
                    );
                }
                );

            tmp = $('#picedit_right #edit').attr("src");

            if (tmp.match(/\.jpg/m)) {

                jQuery("a[href*="+im_image_id+"]", top.document).find('img:first').attr('src', tmp.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));


            } else {
                jQuery("a[href*="+im_image_id+"]", top.document).find('img:first').attr('src', tmp);
                }

            $('#picedit_right #edit').attr("src", tmp);
            apply_size(w,h);

        }

        function centerBox(){
         //based on fancybox scrollBox function
           var w = parent.jQuery.fn.fancybox.getViewport();
           var ow	= $("#fancy_outer", top.document).outerWidth();
           var oh	= $("#fancy_outer", top.document).outerHeight();
           var pos	= {
              	'top'	: (oh > w[1] ? w[3] : w[3] + Math.round((w[1] - oh) * 0.5)),
			          'left'	: (ow > w[0] ? w[2] : w[2] + Math.round((w[0] - ow) * 0.5))
		            };
          $("#fancy_outer", top.document).css(pos);
        }

        function loader() {
            parent.jQuery.fn.fancybox.showLoading();
        }

        function infobox(msg) {
          if ($.browser.msie)
            $('#image-edit-caption').width($('#picedit_right #edit').width());
          else
            $('#image-edit-caption').width($('#picedit_right #edit').width()-10);
          $('#image-edit-info').html(zetaprints_trans(msg));
          $('#image-edit-info').show('fast', function()
            {
              var cw = 0;
              $('#image-edit-caption span').each(function()
                {
                  cw += $(this).width();
                }
              );
              if (cw<280)cw = 280;
              if ($('#picedit_right #edit').width()<cw)
              {
                $('#image-edit-caption').width(cw);
                apply_size(cw, $('#picedit_right #edit').height());
              }

            }
          );
        }


        function apply_size(w, h) {
          //min dimensions
          if (w<300||typeof(w)=="undefined")w = 300;
          if (h<300||typeof(h)=="undefined")h = 300;
          $('#fancy_outer', top.document).width(Number(w)+120);
          $('#fancy_outer', top.document).height(Number(h)+75);
          centerBox();
        }

        $('#crop').click(crop);
        $('#apply_crop').click(apply_crop);
        $('#restore').click(restore);
        $('#rotate').click(function()
        {
            dorotate('r');
        }
        );
        $('#rotatel').click(function()
        {
            dorotate('l');
        }
        );
    }
    );
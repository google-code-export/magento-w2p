jQuery(document).ready(function($)
    {
        var imageEditorJcropApi;
        $('#fancy_frame',top.document).css('overflow','hidden');
        $('#fancy_frame',top.document).css('display','block');
        imageEditorLoadImage();

        function imageEditorCrop()
        {
            imageEditorHideCrop();
            imageEditorInfoBox('Use triggers to crop image');
            imageEditorJcropApi = $.Jcrop('#imageEditorRight #imageEditorPreview');
            imageEditorJcropApi.setOptions(
            {
                onSelect: imageEditorUpdateCropCoords,
                onChange: imageEditorUpdateCropCoords
            }
            );
            imageEditorJcropApi.setSelect([Number($('#imageEditorRight #imageEditorPreview').width())*Number(0.9), Number($('#imageEditorRight #imageEditorPreview').height())*Number(0.9), Number($('#imageEditorRight #imageEditorPreview').width())*Number(0.1), Number($('#imageEditorRight #imageEditorPreview').height())*Number(0.1)]);
            $('#imageEditorCropForm').css('display', 'block');
        }


        function imageEditorHideCrop() {
            $('#imageEditorInfo').hide();
            $('#imageEditorCropForm').css('display', 'none');
            if (typeof(imageEditorJcropApi) != "undefined") {
                imageEditorJcropApi.destroy();
            }
        }
        function imageEditorUpdateCropCoords(c)
        {
            $('#imageEditorCropX').val(c.x);
            $('#imageEditorCropY').val(c.y);
            $('#imageEditorCropX2').val(c.x2);
            $('#imageEditorCropY2').val(c.y2);
            $('#imageEditorCropW').val(c.w);
            $('#imageEditorCropH').val(c.h);
            $('#imageEditorHeightInfo').html(c.h+' px');
            $('#imageEditorWidthInfo').html(c.w+' px');

        }

        function imageEditorApplyCrop() {
            imageEditorHideCrop();
            imageEditorLoader();
            $.ajax(
            {
                url: imageEditorUpdateURL+'?CropX1='+$('#imageEditorCropX').val()+imageEditorDelimeter+'CropY1='+$('#imageEditorCropY').val()+imageEditorDelimeter+'CropX2='+$('#imageEditorCropX2').val()+imageEditorDelimeter+'CropY2='+$('#imageEditorCropY2').val()+imageEditorDelimeter+'page=img-crop'+imageEditorDelimeter+'ImageID='+imageEditorId+imageEditorQueryAppend,
                type: 'POST',
                data: 'zetaprints-CropX='+$('#imageEditorCropX').val()+imageEditorDelimeter+'zetaprints-CropY='+$('#imageEditorCropY').val()+imageEditorDelimeter+'zetaprints-CropX2='+$('#imageEditorCropX2').val()+imageEditorDelimeter+'zetaprints-CropY2='+$('#imageEditorCropY2').val()+imageEditorDelimeter+'zetaprints-action=img-crop'+imageEditorDelimeter+'zetaprints-ImageID='+imageEditorId+imageEditorQueryAppend,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t crop image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    imageEditorApplyImage(data);
                    imageEditorInfoBox('Image Cropped');
                }
            }
            );
        }

        function imageEditorRestore() {
            imageEditorHideCrop();
            imageEditorLoader();
            $.ajax(
            {
                url: imageEditorUpdateURL+'?page=img-undo'+imageEditorDelimeter+'ImageID='+imageEditorId+imageEditorQueryAppend,
                type: 'POST',
                data: 'zetaprints-action=img-restore&zetaprints-ImageID='+imageEditorId+imageEditorQueryAppend,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t restore image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    imageEditorApplyImage(data);
                    imageEditorInfoBox('Image Restored');
                }
            }
            );
        }
        function imageEditorLoadImage() {
            imageEditorLoader();
            $.ajax(
            {
                url: imageEditorUpdateURL+'?page=img-props'+imageEditorDelimeter+'ImageID='+imageEditorId+imageEditorQueryAppend,
                type: 'POST',
                datatype: 'XML',
                data: 'zetaprints-action=img&zetaprints-ImageID='+imageEditorId+imageEditorQueryAppend,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t load image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    imageEditorApplyImage(data);
                    imageEditorInfoBox('Image Loaded');
                }
            }
            );

        }
        function imageEditorDoRotate(dir) {
            imageEditorHideCrop();
            imageEditorLoader();
            $.ajax(
            {
                url: imageEditorUpdateURL+'?page=img-rot'+imageEditorDelimeter+'Rotation='+dir+imageEditorDelimeter+'ImageID='+imageEditorId+imageEditorQueryAppend,
                type: 'POST',
                data: 'zetaprints-action=img-rotate&zetaprints-Rotation='+dir+'&zetaprints-ImageID='+imageEditorId+imageEditorQueryAppend,
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert(zetaprints_trans('Can\'t rotate image:') + ' ' + textStatus);
                },
                success: function (data, textStatus) {
                    imageEditorApplyImage(data);
                    imageEditorInfoBox('Image Rotated');
                }
            }
            );
        }
        function imageEditorApplyImage(xml) {
            var h, w, uh,uw;
            $('#imageEditorRight #imageEditorPreview').hide();
            $('#imageEditorCaption').hide();
            imageEditorLoader();
            if(! window.DOMParser)
            {
                var xmlDoc = null;
                xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
                xmlDoc.async = false;
                xmlDoc.loadXML(xml);
                var t = $(xmlDoc);
                t.find('Image').each(function()
                {

                    $('#imageEditorRight #imageEditorPreview').attr("src", imageEditorZpURL+'/photothumbs/'+$(this).attr('Thumb'));

                    $('#imageEditorRight #imageEditorPreview').height($(this).attr('ThumbHeight'));
                    $('#imageEditorRight #imageEditorPreview').width($(this).attr('ThumbWidth'));
                    $('#imageEditorHeightInfo').html($(this).attr('ImageHeight')+' px');
                    $('#imageEditorWidthInfo').html($(this).attr('ImageWidth')+' px');


                    h = $(this).attr('ThumbHeight');
                    w = $(this).attr('ThumbWidth');

                    uh = $(this).attr('ImageHeightUndo');
                    if (typeof(uh)=="undefined")uh=$(this).attr('ImageHeight');
                    uw = $(this).attr('ImageWidthUndo');
                    if (typeof(uw)=="undefined")uw=$(this).attr('ImageWidth');

                }
                );
            }
            else {
                t = $(xml);
                t.find('img').each(function()
                {

                    $('#imageEditorRight #imageEditorPreview').attr("src", imageEditorZpURL+'/photothumbs/'+$(this).attr('thumb'));

                    $('#imageEditorRight #imageEditorPreview').height($(this).attr('thumbheight'));
                    $('#imageEditorRight #imageEditorPreview').width($(this).attr('thumbwidth'));
                    $('#imageEditorHeightInfo').html($(this).attr('imageheight')+' px');
                    $('#imageEditorWidthInfo').html($(this).attr('imagewidth')+' px');

                    h = $(this).attr('thumbheight');
                    w = $(this).attr('thumbwidth');

                    uh = $(this).attr('imageheightundo');
                    if (typeof(uh)=="undefined")uh=$(this).attr('imageheight');
                    uw = $(this).attr('imagewidthundo');
                    if (typeof(uw)=="undefined")uw=$(this).attr('imagewidth');

                }
                );
            }
            $('#imageEditorLeft #imageEditorRestore').attr('title',zetaprints_trans('Undo all changes')+'. '+zetaprints_trans('Original size')+': '+uw+' x '+uh+' px.');

            $('#imageEditorRight #imageEditorPreview')
            .load(
                function() {
                    $('#imageEditorRight #imageEditorPreview').fadeIn().ready(function ()
                    {
                        parent.jQuery('#fancy_loading').fadeOut();
                        $('#imageEditorCaption').show();
                    }
                    );
                }
                );

            tmp = $('#imageEditorRight #imageEditorPreview').attr("src");
            tmp1 = jQuery("a[href*="+imageEditorId+"]", top.document).find('img:first');
            if (tmp1.length==0){
            tmp1 = jQuery('#img'+imageEditorId, top.document);
            }

            if (tmp.match(/\.jpg/m)) {

                tmp1.attr('src', tmp.replace(/\.(jpg|gif|png|jpeg|bmp)/i, "_0x100.jpg"));


            } else {
                tmp1.attr('src', tmp);
                }

            $('#imageEditorRight #imageEditorPreview').attr("src", tmp);
            imageEditorApplySize(w,h);

        }

        function imageEditorCenterBox(){
         //based on fancybox scrollBox function
          if (typeof(parent.jQuery.fn.fancybox)!="undefined")
            var w = parent.jQuery.fn.fancybox.getViewport();
            else
            if (typeof(jQuery.fn.fancybox)!="undefined")
            var w = jQuery.fn.fancybox.getViewport();
            else return false;
           var w = parent.jQuery.fn.fancybox.getViewport();
           var ow	= $("#fancy_outer", top.document).outerWidth();
           var oh	= $("#fancy_outer", top.document).outerHeight();
           var pos	= {
              	'top'	: (oh > w[1] ? w[3] : w[3] + Math.round((w[1] - oh) * 0.5)),
			          'left'	: (ow > w[0] ? w[2] : w[2] + Math.round((w[0] - ow) * 0.5))
		            };
          $("#fancy_outer", top.document).css(pos);
        }

        function imageEditorLoader() {
            if (typeof(parent.jQuery.fn.fancybox)!="undefined")
            parent.jQuery.fn.fancybox.showLoading();
            else
            if (typeof(jQuery.fn.fancybox)!="undefined")
            jQuery.fn.fancybox.showLoading();
        }

        function imageEditorInfoBox(msg) {
        $('#imageEditorCaption').show();
          if ($.browser.msie)
            $('#imageEditorCaption').width($('#imageEditorRight #imageEditorPreview').width());
          else
            $('#imageEditorCaption').width($('#imageEditorRight #imageEditorPreview').width()-10);
          $('#imageEditorInfo').html(zetaprints_trans(msg));
          $('#imageEditorInfo').show('fast', function()
            {
              var cw = 0;
              $('#imageEditorCaption span').each(function()
                {
                  cw += $(this).width();
                }
              );
              if (cw<280)cw = 280;
              if ($('#imageEditorRight #imageEditorPreview').width()<cw)
              {
                $('#imageEditorCaption').width(cw);
                imageEditorApplySize(cw, $('#imageEditorRight #imageEditorPreview').height());
              }

            }
          );
        }


        function imageEditorApplySize(w, h) {
          //min dimensions
          if (w<300||typeof(w)=="undefined")w = 300;
          if (h<300||typeof(h)=="undefined")h = 300;
          $('#fancy_outer', top.document).width(Number(w)+120);
          $('#fancy_outer', top.document).height(Number(h)+75);
          imageEditorCenterBox();
        }

        if (!window.zetaprints_trans){
          function zetaprints_trans(msg){
            return msg;
          }
        }

        $('#imageEditorCrop').click(imageEditorCrop);
        $('#imageEditorApplyCrop').click(imageEditorApplyCrop);
        $('#imageEditorRestore').click(imageEditorRestore);
        $('#imageEditorRotateRight').click(function()
        {
            imageEditorDoRotate('r');
        }
        );
        $('#imageEditorRotateLeft').click(function()
        {
            imageEditorDoRotate('l');
        }
        );
    }
    );
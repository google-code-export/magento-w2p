/**
* 
*/
function addResizeOption(opts)
{
  // check if displayed size is smaller than loaded image
  var img = jQuery('#fancybox-img');
  var img_width = opts.width;
  var img_height = opts.height;
  var height = img.height();
  var width = img.width();
  // if it is, add max/restore button, do it if fancybox loads only image
  if (img_width > width && opts.type == 'image' && !jQuery('#fancybox-resize').length) {
    var outer = jQuery('#fancybox-outer');
    outer.append(jQuery('<div id="fancybox-resize"><a class="maximize" style="display: none;"></a><a class="restore" style="display: none;"></a></div>'));
    var parent = jQuery('#fancybox-resize');
    parent.data('h', height).data('w', width).data('orig_w', img_width).data('orig_h', img_height);
    jQuery('a', '#fancybox-resize').each(function()
    {
      if (jQuery(this).hasClass('maximize')) {
        jQuery(this).show();
      }
      jQuery(this).click(function()
      {
        var self = jQuery(this);
        self.hide();
        var data = jQuery('#fancybox-resize').data();
        var diff_x = data.orig_w - data.w;
        var diff_y = data.orig_h - data.h;
        if (self.hasClass('maximize')) {
          fancyResize(diff_x, diff_y);
          jQuery('a.restore', '#fancybox-resize').first().show();
        } else if (self.hasClass('restore')) {
          fancyResize(-diff_x, -diff_y);
          jQuery('a.maximize', '#fancybox-resize').first().show();
        }
      });
    });
  } else if (img_width == width && jQuery('#fancybox-resize').length) {
    // picture is now with exatly same dimensions, and tere was a resizer
    jQuery('#fancybox-resize').remove();
  } else {
    jQuery('#fancybox-resize').data('w', width).data('h', height);
    jQuery('a.restore', '#fancybox-resize').hide();
    jQuery('a.maximize', '#fancybox-resize').show();
  }
}

function fancyResize(diff_x, diff_y)
{
  jQuery('#fancybox-wrap').width(jQuery('#fancybox-wrap').width() + diff_x);
  jQuery('#fancybox-content').width(jQuery('#fancybox-content').width() + diff_x);
  jQuery('#fancybox-content').height(jQuery('#fancybox-content').height() + diff_y);
  jQuery.fancybox.center(true);
}

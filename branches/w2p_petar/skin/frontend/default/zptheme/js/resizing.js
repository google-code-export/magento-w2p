/**
 * Add resize
 *
 * Adds resizing capabilities to fancybox for those occasions
 * where borwser window is smaller than needed for to display
 * entire image size. Maximizing the image should expand it to its full
 * size. Restore should bring it back to its previous size.
 * If browser window size is changed after fancybox is shown, that will
 * not update restore dimensions.
 * Restore dimensions WILL be updated when fancybox is closed and
 * reopened.
 * @param opts
 */
function addResizeOption(opts)
{
  // ref to displayed image
  var img = jQuery('#fancybox-img');

  // actual dimensions
  var height = img.height();
  var width = img.width();

  // displayed dimensions
  var img_width = opts.width;
  var img_height = opts.height;

  //check if displayed size is smaller than loaded image
  // if it is, add max/restore button, do it if fancybox loads image only; if needed will enable for
  // other tyes too, but they will need to have defined width and height
  if (img_width > width && opts.type == 'image' && !jQuery('#fancybox-resize').length) {
    var outer = jQuery('#fancybox-outer'); // get outer container
    // add resizer HTML to it
    outer.append(jQuery('<div id="fancybox-resize"><a class="maximize" style="display: none;"></a><a class="restore" style="display: none;"></a></div>'));
    // get reference of resizer container
    var parent = jQuery('#fancybox-resize');
    // inject some usefull data in it (original image dimensions and current dimensions)
    parent.data('h', height).data('w', width).data('orig_w', img_width).data('orig_h', img_height);
    // update close icon to use custom background
    jQuery('#fancybox-close').css('background-position', '-68px -200px');

    // cycle 'maximize'/'restore' links
    jQuery('a', '#fancybox-resize').each(function()
    {
      if (jQuery(this).hasClass('maximize')) { // if it is maximize show it
        jQuery(this).show();
      }
      jQuery(this).click(function() // add click handler
      {
        var self = jQuery(this); // get ref to clicked link
        self.hide(); // hide it
        var data = jQuery('#fancybox-resize').data(); // get stored data
        var diff_x = data.orig_w - data.w; // calculate difference in real and displayed dimensions
        var diff_y = data.orig_h - data.h;
        if (self.hasClass('maximize')) { // if we are maximizing
          fancyResize(diff_x, diff_y); // add diff
          jQuery('a.restore', '#fancybox-resize').first().show(); // show restore link
        } else if (self.hasClass('restore')) { // if we are restoring
          fancyResize(-diff_x, -diff_y); // subtract diff
          jQuery('a.maximize', '#fancybox-resize').first().show(); // show miximize link
        }
      });
    });
  } else if (img_width == width) {
    // picture is now with exatly same dimensions
    if(jQuery('#fancybox-resize').length > 0){
      // and tere is a resizer
      jQuery('#fancybox-resize').remove(); // remove resizer
      jQuery('#fancybox-close').css('background-position', '-40px 0px'); // reset close icon
    }
  } else {
    // we have already created resizer and this is just reopening of the fancybox
    // we are updating data in resizer to acommodate for cases where browser
    // window has been resized in mean time and 'restore' dimensions are changed
    jQuery('#fancybox-resize').data('w', width).data('h', height);

    // make sure that 'maximize' handle is visible and restore hidden
    jQuery('a.restore', '#fancybox-resize').hide();
    jQuery('a.maximize', '#fancybox-resize').show();
  }
}

/**
 * Do resize
 *
 * Perform actual resizing by adding differences to various
 * elements of fancybox.
 * For out case we need to alter only wrap and inner container, if
 * resizing is to be used with other options enabled, such as title, overlay etc.
 * thi is the place to add other calculations.
 * Difference is always added so when restoring, we need to pass negative difference.
 *
 * @param diff_x integer
 * @param diff_y integer - difference in pixels
 */
function fancyResize(diff_x, diff_y)
{
  // wrap height is set to auto by default so we need to update only width
  jQuery('#fancybox-wrap').width(jQuery('#fancybox-wrap').width() + diff_x);
  // get image container, it has both width and height set explicitly
  var container = _get_content_container();
  // add diffs to it.
  container.width(container.width() + diff_x);
  container.height(container.height() + diff_y);

  // center it to page
  jQuery.fancybox.center(true);
}

/**
 * Aparently between 1.3.1 and 1.3.4(current) versions of fancybox there is change of
 * id for immidiate image container. This function tries to handle this.
 *
 * @returns Object jQuery object representing image container
 */
function _get_content_container()
{
  if(document.getElementById('fancybox-content')){
    return jQuery('#fancybox-content');
  }else if(document.getElementById('fancybox-inner')){
    return jQuery('#fancybox-inner');
  }
  throw ('Fancybix version not supported')
}

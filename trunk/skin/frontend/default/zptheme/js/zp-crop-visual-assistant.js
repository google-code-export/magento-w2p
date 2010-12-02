/**
 *
 *
 *
 */
function cropVisualAssistant ()
{
  this.userImage = {}
  this.userImageThumb = {};
  this.templatePreview = {};
  this.templatePreviewPlaceholder = {}
  this.templateImage = {}
  // $ = jQuery;

  /**
   * Init settings for the UserImage
   *
   * @attr: _element - image uploaded by user
   * @attr: _widthActual - actual width of user image
   * @attr: _heightActual - actual height of user image
   * @attr: _widthPreview - preview width of user image
   * @attr: _heightPreview - preview height of user image
   */
  this.setUserImage = function (_element, _widthActual, _heightActual, _widthPreview, _heightPreview)
  {
    this.userImage = {
      element: _element,
      widthActualPx: _widthActual,
      heightActualPx: _heightActual,
      widthPreviewPx: _widthPreview,
      heightPreviewPx: _heightPreview,
      aspectRatio: _widthActual/_heightActual,
      scaleCoef: _widthActual/_widthPreview
    }
  }

  /**
   * Init settings for the UserImageThumb
   *
   * @attr: _element - affected thumb image
   */
  this.setUserImageThumb = function (_element)
  {
    this.userImageThumb = {
      element: _element,
      widthPx: _element.width(),
      heightPx: _element.height()
    }
  }

  /**
   * Get GUID of UserImageThumb (by value stored in CSS class)
   *
   * @return: GUID
   */
  this.getUserImageThumbGuid = function () {
    return jQuery.trim(this.userImageThumb.element.attr('id'));
  }

  /**
   * Init settings for the TemplatePreview
   *
   * @attr: _templatePreviewElement - affected TemplatePreview image
   */
  this.setTemplatePreview = function ($templatePreviewElement)
  {
    var page = top.zp.template_details.pages[top.zp.current_page];
    var shape = page.shapes[top.image_imageName];

    if (shape) {
      shape['anchor-x'] = shape['anchor-x'] / page['width-in'];
      shape['anchor-y'] = shape['anchor-y'] / page['height-in'];

      this.templatePreviewPlaceholder = shape;
    }

    var image = page.images[top.image_imageName]

    if (image != undefined)
      this.templateImage = {
        clipped: (image['clipped']) ? true : false,
        widthIn: page['width-in'] * (this.templatePreviewPlaceholder.x2 - this.templatePreviewPlaceholder.x1),
        widthPx: image['width'],
        heightPx: image['height'],
        aspectRatio: image['width'] / image['height'] };

    this.templatePreview = {
      element: $templatePreviewElement,
      width: $templatePreviewElement.width(),
      height: $templatePreviewElement.height()
    }
  }

  /**
   * Init preview if need to, do the calculations and update cropped area
   *
   */
  this.updateView = function (_cropArea, image_position, image_size)
  {
    if (this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').length==0)
      this.cropedAreaSet(
        this.userImageThumb.element,
        this.userImageThumb.element.attr("src")
      );

    var _cropArea2 = [_cropArea[0]/this.userImage.widthPreviewPx,
                      _cropArea[1]/this.userImage.heightPreviewPx,
                      _cropArea[2]/this.userImage.widthPreviewPx,
                      _cropArea[3]/this.userImage.heightPreviewPx]

    var _cropAreaLeft = Math.round(this.userImageThumb.widthPx * _cropArea2[0]);
    var _cropAreaTop = Math.round(this.userImageThumb.heightPx * _cropArea2[1]);
    var _cropAreaWidth = Math.round(this.userImageThumb.widthPx * _cropArea2[2]) - _cropAreaLeft;
    var _cropAreaHeight = Math.round(this.userImageThumb.heightPx * _cropArea2[3]) - _cropAreaTop;

    if (image_position && image_size) {
      image_left =  this.userImageThumb.widthPx / image_position.left;
      image_top = this.userImageThumb.HeightPx / image_position.top;

      image_width =  this.userImageThumb.widthPx / image_size.width,

      this.cropedAreaUpdate(this.userImageThumb.element, 0, 0,
        this.userImageThumb.widthPx, this.userImageThumb.HeightPx, -image_left,
        -image_top, image_width );
    } else
      this.cropedAreaUpdate(
        this.userImageThumb.element,
        _cropAreaLeft,
        _cropAreaTop,
        _cropAreaWidth,
        _cropAreaHeight,
        _cropAreaLeft,
        _cropAreaTop,
        this.userImageThumb.widthPx );
  }

  /**
   * Obtain initial coordinates of the cropped area (exactly as it would be
   * if one used the "obtain preview" action without defining any crop area)
   *
   * @attr:
   */
  this.getInitCroppedArea = function ()
  {
    // not used ------------
    var userImage_AnchorX = this.userImage.widthPreviewPx * this.templatePreviewPlaceholder['anchor-x'];
    var userImage_AnchorY = this.userImage.heightPreviewPx * this.templatePreviewPlaceholder['anchor-y'];

    var placeholderToImageRel = this.templateImage.widthPx / (this.templatePreviewPlaceholder.x2 - this.templatePreviewPlaceholder.x1);

    var imageAnchorXPx = this.templatePreviewPlaceholder.anchorx * placeholderToImageRel;
    var imageAnchorYPx = this.templatePreviewPlaceholder.anchory * placeholderToImageRel;
    // not used (end) ------

    // temporary solution:
    var _x = 0;
    var _y = 0;
    var _w = this.userImage.widthPreviewPx;
    var _h = this.userImage.heightPreviewPx;

    if (this.templateImage.aspectRatio > this.userImage.aspectRatio) {
      _h = this.userImage.widthPreviewPx / this.templateImage.aspectRatio;
      _y = (this.userImage.heightPreviewPx - _h) / 2;
    } else {
      _w = this.userImage.heightPreviewPx * this.templateImage.aspectRatio;
      _x = (this.userImage.widthPreviewPx - _w) / 2;
    }

    return [_x, _y, _x + _w, _y + _h];
  }

  /**
   * Get information about placeholder and corresponding image
   */
  this.getPlaceholderInfo = function ()
  {
    return {
      clipped: this.templateImage.clipped,
      widthIn: this.templateImage.widthIn,
      widthPx: this.templateImage.widthPx,
      heightPx: this.templateImage.heightPx,
      userImageScaleCoef: this.userImage.scaleCoef,
      resolution: Math.round(this.templateImage.widthPx / this.templateImage.widthIn)
    }
  }

  /**
   * Get resulting image resolution
   *
   *@attr: _userSelectedWidth - user selected width
   */
  this.getResultingImageResolution = function (_userSelectedWidth, _userSelectedHeight)
  {
    var userSelectedActualWidth = _userSelectedWidth * this.getPlaceholderInfo().userImageScaleCoef;
    var userSelectedActualHeight = _userSelectedHeight * this.getPlaceholderInfo().userImageScaleCoef;
    /*
     * Image resolution does not change only if the image is smaller than the placeholder or clipped=false
     */
    if (
      this.getPlaceholderInfo().clipped == true ||
      userSelectedActualWidth > this.templateImage.widthPx ||
      userSelectedActualHeight > this.templateImage.heightPx
    )
      return Math.round(userSelectedActualWidth / this.getPlaceholderInfo().widthIn);
    else
      return this.getPlaceholderInfo().resolution;
  }

  /**
   *
   */
  this.getInfoBar = function ()
  {
    var infoBar = jQuery(
      '<STYLE type="text/css">' +
        '#infobar {border-collapse: collapse; color: white; font:12px Arial;}' +
        '#infobar td {padding: 2px 4px 2px 4px; white-space:nowrap;}' +
        '#infobar_uploaded_image td {background-color: #5275a4; border: 1px solid #5275a4;}' +
        '#infobar_image_field td {background-color: #7192bf; border: 1px solid #7192bf;}' +
        '#infobar #infobar_uploaded_image_width {min-width:25px;text-align:center;background-color:white; color:black;}' +
        '#infobar #infobar_uploaded_image_height {min-width:25px;text-align:center;background-color:white; color:black;}' +
        '#infobar #infobar_uploaded_image_dpi {min-width:25px;text-align:center;background-color:white; color:black;}' +
        '#infobar #infobar_image_field_width {min-width:25px;text-align:center;}' +
        '#infobar #infobar_image_field_height {min-width:25px;text-align:center;}' +
        '#infobar #infobar_image_field_dpi {min-width:25px;text-align:center;}' +
        '#infobar .infobar_tooltip {background-color:transparent; border:none; font-weight:bold; color:black;}' +
        '#infobar_tooltip_uploaded_image_icon {background-position: 50% 50%; background-repeat:no-repeat;}' +
        '#infobar_tooltip_image_field_icon {background-position: 50% 50%; background-repeat:no-repeat;}' +
      '</STYLE>' +
      '<TABLE id="infobar"><TBODY>' +
      '<TR id="infobar_uploaded_image">' +
        '<TD>' + zetaprints_trans('Uploaded image') + ':</TD>' +
        '<TD id="infobar_uploaded_image_width"></TD>' +
        '<TD>x</TD>' +
        '<TD id="infobar_uploaded_image_height"></TD>' +
        '<TD>px</TD>' +
        '<TD>at</TD>' +
        '<TD id="infobar_uploaded_image_dpi"></TD>' +
        '<TD>dpi</TD>' +
        '<TD class="infobar_tooltip" id="infobar_tooltip_uploaded_image_icon"><DIV style="width:12px;height:12px;"><SPACER type="block" width="12" height="12"></DIV></TD>' +
        '<TD class="infobar_tooltip" id="infobar_tooltip_uploaded_image"></TD>' +
      '</TR>' +
      '<TR><TD colspan="10" style="height:1px;background-color:transparent;padding:0;">' +
        '<DIV style="width:1px;height:1px;"><SPACER type="block" width="1" height="1"></DIV>' +
      '</TD></TR>' +
      '<TR id="infobar_image_field">' +
        '<TD>' + zetaprints_trans('Image field') + ':</TD>' +
        '<TD id="infobar_image_field_width">' + this.getPlaceholderInfo().widthPx + '</TD>' +
        '<TD>x</TD>' +
        '<TD id="infobar_image_field_height">' + this.getPlaceholderInfo().heightPx + '</TD>' +
        '<TD>px</TD>' +
        '<TD>at</TD>' +
        '<TD id="infobar_image_field_dpi">' + this.getPlaceholderInfo().resolution + '</TD>' +
        '<TD>dpi</TD>' +
        '<TD class="infobar_tooltip" id="infobar_tooltip_image_field_icon"><DIV style="width:12px;height:12px;"><SPACER type="block" width="12" height="12"></DIV></TD>' +
        '<TD class="infobar_tooltip" id="infobar_tooltip_image_field"></TD>' +
      '</TR>' +
      '</TBODY></TABLE>'
    );

    return infoBar;
  }

  /**
   *
   */
  this.updateInfoBarTooltip = function (
    _messageType,
    _messageText
  )
  {
    jQuery('#infobar_tooltip_uploaded_image_icon').css({
      backgroundImage: 'url(' + skinUrl + 'infobar_icon_tooltip_' + ((_messageType=='fatal') ? 'red' : 'white') + '.gif)'
    })
    jQuery('#infobar_tooltip_uploaded_image,#infobar_uploaded_image_width,#infobar_uploaded_image_height,#infobar_uploaded_image_dpi').css({
      color: (_messageType=='fatal') ? '#db0d0d' : 'black'
    })
    jQuery('#infobar_tooltip_uploaded_image').html(_messageText);
  }

  /**
   *
   */
  this.updateInfoBar = function (
    _infobar_uploaded_image_width,
    _infobar_uploaded_image_height
  )
  {
    var resultingImageResolution = this.getResultingImageResolution(_infobar_uploaded_image_width, _infobar_uploaded_image_height);
    jQuery('#infobar_uploaded_image_width').html(_infobar_uploaded_image_width);
    jQuery('#infobar_uploaded_image_height').html(_infobar_uploaded_image_height);
    jQuery('#infobar_uploaded_image_dpi').html(resultingImageResolution);

    if (resultingImageResolution < this.getPlaceholderInfo().resolution) {
      this.updateInfoBarTooltip('fatal', 'Image too small');
    } else {
      this.updateInfoBarTooltip('info', '');
    }
  }

  /**
   * Update cropped area (according to the user manipulations with the crop frame)
   *
   * @attr: _targetImageElement - affected image
   * @attr: _cropAreaLeft - left coord of crop frame (px)
   * @attr: _cropAreaTop - top coord of crop frame (px)
   * @attr: _cropAreaWidth - width of crop frame (px)
   * @attr: _cropAreaHeight - height of crop frame (px)
   * @attr: _clipedImageLeft - shift of clipped image to the left with relative to the crop frame (px)
   * @attr: _clipedImageTop - shift of clipped image to the top with relative to the crop frame (px)
   * @attr: _clipedImageWidth - width of the displayed image (for changing scale of displayed image)
   */
  this.cropedAreaUpdate = function (
    _targetImageElement,
    _cropAreaLeft,
    _cropAreaTop,
    _cropAreaWidth,
    _cropAreaHeight,
    _clipedImageLeft,
    _clipedImageTop,
    _clipedImageWidth
  )
  {
    var _toolSet = _targetImageElement.prev('div.thumbCropedAreaToolSet');
    var _cropAreaDiv = jQuery('div', _toolSet);

    _cropAreaDiv.css({
      left: _cropAreaLeft,
      top: _cropAreaTop,
      height: _cropAreaHeight,
      width: _cropAreaWidth
    });
    var _cropImg = jQuery('img', _cropAreaDiv);
    _cropImg.css({
      left: -_clipedImageLeft,
      top: -_clipedImageTop
    })
  }

  /**
   * Set cropped area
   *
   * @attr: _targetImageElement - affected image
   * @attr: _clipedImageSrc - image url used for displaying in clipped area
   * @attr: _targetImageOverheadStyle - css rules for outside area (backgroundColor and opacity)
   */
  this.cropedAreaSet = function (_targetImageElement, _clipedImageSrc ) {
    var _toolSet = jQuery('<DIV />');
    _toolSet.css({
      marginBottom: -_targetImageElement.height(),
      height: _targetImageElement.height()
    }).attr('class', 'thumbCropedAreaToolSet').insertBefore(_targetImageElement);

    var _cropAreaDiv = jQuery('<DIV />');
    _cropAreaDiv.appendTo(_toolSet);

    var _cropImg = jQuery('<IMG src="' + _clipedImageSrc + '" />')
    _cropImg.appendTo(_cropAreaDiv);
  }

  /**
   * Remove cropped area
   *
   */
  this.cropedAreaRemove = function () {
    // this.templatePreview.element.prev('div.thumbCropedAreaToolSet').remove();
    this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').remove();
  }

  /**
   * Hide cropped area
   *
   */
  this.cropedAreaHide = function () {
    this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').hide();
  }

  /**
   * Show cropped area
   *
   */
  this.cropedAreaShow = function () {
    this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').show();
  }
}

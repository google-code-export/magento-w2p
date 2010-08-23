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
   * Init settings for the TemplatePreview
   *
   * @attr: _templatePreviewElement - affected TemplatePreview image
   */
  this.setTemplatePreview = function (_templatePreviewElement)
  {
    for (var pageNum in top.shapes) {
      if (top.shapes[pageNum][top.image_imageName] != undefined) {
        _templatePreviewPlaceholder = top.shapes[pageNum][top.image_imageName];

        //@Temporary plug for transform inches values to relative ones:
        //(not used yet)
        _templatePreviewPlaceholder['anchorx'] = _templatePreviewPlaceholder['anchorx'] / top.pages[pageNum]['width-in'];
        _templatePreviewPlaceholder['anchory'] = _templatePreviewPlaceholder['anchory'] / top.pages[pageNum]['height-in'];
      }
    }
    this.templatePreviewPlaceholder = _templatePreviewPlaceholder

    for (var pageNum in top.images) {
      if (top.images[pageNum][top.image_imageName] != undefined) {
        var image_data = top.images[pageNum][top.image_imageName];
        _templateImage = {
          clipped: (image_data['clipped']) ? true : false,
          widthIn: top.pages[pageNum]['width-in'] * (this.templatePreviewPlaceholder.x2 - this.templatePreviewPlaceholder.x1),
          widthPx: image_data['width'],
          heightPx: image_data['height'],
          aspectRatio: image_data['width'] / image_data['height']
        };
      }
    }
    this.templateImage = _templateImage;

    this.templatePreview = {
      element: _templatePreviewElement,
      width: _templatePreviewElement.width(),
      height: _templatePreviewElement.height()
    }
  }

  /**
   * Init preview if need to, do the calculations and update cropped area
   *
   */
  this.updateView = function (_cropArea)
  {
    if (this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').length==0)
      this.cropedAreaSet(
        this.userImageThumb.element,
        this.userImageThumb.element.attr("src"),
        {backgroundColor:'black', opacity:'0.7'}
      );

    var _cropArea2 = [_cropArea[0]/this.userImage.widthPreviewPx, _cropArea[1]/this.userImage.heightPreviewPx, _cropArea[2]/this.userImage.widthPreviewPx, _cropArea[3]/this.userImage.heightPreviewPx]

    var _cropAreaLeft = Math.round(this.userImageThumb.widthPx * _cropArea2[0]);
    var _cropAreaTop = Math.round(this.userImageThumb.heightPx * _cropArea2[1]);
    var _cropAreaWidth = Math.round(this.userImageThumb.widthPx * _cropArea2[2]) - _cropAreaLeft;
    var _cropAreaHeight = Math.round(this.userImageThumb.heightPx * _cropArea2[3]) - _cropAreaTop;

    this.cropedAreaUpdate(
      this.userImageThumb.element,
      _cropAreaLeft,
      _cropAreaTop,
      _cropAreaWidth,
      _cropAreaHeight,
      _cropAreaLeft,
      _cropAreaTop,
      this.userImageThumb.widthPx
    );
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
    var userImage_AnchorX = this.userImage.widthPreviewPx * this.templatePreviewPlaceholder['anchorx'];
    var userImage_AnchorY = this.userImage.heightPreviewPx * this.templatePreviewPlaceholder['anchory'];

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
      width: _cropAreaWidth,
      height: _cropAreaHeight
    });
    var _cropImg = jQuery('img', _cropAreaDiv);
    _cropImg.css({
      left: -_clipedImageLeft,
      top: -_clipedImageTop,
      width: _clipedImageWidth + 'px',
      maxWidth: _clipedImageWidth + 'px'
    })
  }

  /**
   * Set cropped area
   *
   * @attr: _targetImageElement - affected image
   * @attr: _clipedImageSrc - image url used for displaying in clipped area
   * @attr: _targetImageOverheadStyle - css rules for outside area (backgroundColor and opacity)
   */
  this.cropedAreaSet = function (
    _targetImageElement,
    _clipedImageSrc,
    _targetImageOverheadStyle
  )
  {
    var _targetImageElementPos = _targetImageElement.position();
    var _toolSet = jQuery('<DIV />');
    _toolSet.css({
      position: 'absolute',
      left: _targetImageElementPos.left,
      top: _targetImageElementPos.top,
      width: _targetImageElement.width(),
      height: _targetImageElement.height(),
      backgroundColor: _targetImageOverheadStyle.backgroundColor,
      opacity: _targetImageOverheadStyle.opacity
    }).attr('class', 'thumbCropedAreaToolSet').insertBefore(_targetImageElement);

    var _cropAreaDiv = jQuery('<DIV />');
    _cropAreaDiv.css({
      position: 'absolute',
      overflow: 'hidden'
    }).appendTo(_toolSet);

    var _cropImg = jQuery('<IMG src="' + _clipedImageSrc + '" />').css({
      position: 'absolute',
    })
    _cropImg.appendTo(_cropAreaDiv);
  }

  /**
   * Remove cropped area
   *
   */
  this.cropedAreaRemove = function () {
    this.templatePreview.element.prev('div.thumbCropedAreaToolSet').remove();
  }
}
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
  $ = jQuery;

  this.setUserImage = function (_element, _widthActual, _heightActual, _widthPreview, _heightPreview)
  {
    this.userImage = {
      element: _element,
      widthActual: _widthActual,
      heightActual: _heightActual,
      widthPreview: _widthPreview,
      heightPreview: _heightPreview
    }
  }

  this.setUserImageThumb = function (_element)
  {
    this.userImageThumb = {
      element: _element,
      width: _element.width(),
      height: _element.height()
    }
  }

  this.setTemplatePreview = function (_templatePreviewElement)
  {
  	$.each(top.shapes, function() {
      var shape = $(this);
      if (shape[0][top.image_imageName] != undefined) {
        _templatePreviewPlaceholder = shape[0][top.image_imageName];
      }
    });
    this.templatePreviewPlaceholder = _templatePreviewPlaceholder

    this.templatePreview = {
      element: _templatePreviewElement,
      width: _templatePreviewElement.width(),
      height: _templatePreviewElement.height()
    }
  }

  this.updateView = function (_cropArea)
  {
    if (this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').length==0)
      this.cropedAreaSet(
        this.userImageThumb.element,
        this.userImageThumb.element.attr("src"),
        {backgroundColor:'black', opacity:'0.7'}
      );

    if (this.templatePreview.element.prev('div.thumbCropedAreaToolSet').length==0)
      this.cropedAreaSet(
        this.templatePreview.element,
        this.userImage.element.attr("src"),
        {backgroundColor:'transparent', opacity:'1.0'}
      );

    var _cropArea2 = [_cropArea[0]/this.userImage.widthPreview, _cropArea[1]/this.userImage.heightPreview, _cropArea[2]/this.userImage.widthPreview, _cropArea[3]/this.userImage.heightPreview]

    var _cropAreaLeft = Math.round(this.userImageThumb.width * _cropArea2[0]);
    var _cropAreaTop = Math.round(this.userImageThumb.height * _cropArea2[1]);
    var _cropAreaWidth = Math.round(this.userImageThumb.width * _cropArea2[2]) - _cropAreaLeft;
    var _cropAreaHeight = Math.round(this.userImageThumb.height * _cropArea2[3]) - _cropAreaTop;

    this.cropedAreaUpdate(
      this.userImageThumb.element,
      _cropAreaLeft,
      _cropAreaTop,
      _cropAreaWidth,
      _cropAreaHeight,
      _cropAreaLeft,
      _cropAreaTop,
      this.userImageThumb.width
    );

    var _placeholderOffsetLeft = Math.round(this.templatePreviewPlaceholder.x1 * this.templatePreview.width);
    var _placeholderOffsetTop = Math.round(this.templatePreviewPlaceholder.y1 * this.templatePreview.height);
    var _placeholderWidth = Math.round((this.templatePreviewPlaceholder.x2 - this.templatePreviewPlaceholder.x1) * this.templatePreview.width);
    var _placeholderHeight = Math.round((this.templatePreviewPlaceholder.y2 - this.templatePreviewPlaceholder.y1) * this.templatePreview.height);

    var _k = _placeholderWidth / (_cropArea[2] - _cropArea[0]);

    var _clipedImageLeft = Math.round(_k * _cropArea[0])
    var _clipedImageTop = Math.round(_k * _cropArea[1])
    var _clipedImageWidth = Math.round(_k * this.userImage.widthPreview);
    var _clipedImageHeight = Math.round(_k * this.userImage.heightPreview);

    this.cropedAreaUpdate(
      this.templatePreview.element,
      _placeholderOffsetLeft,
      _placeholderOffsetTop,
      _placeholderWidth,
      _placeholderHeight,
      _clipedImageLeft,
      _clipedImageTop,
      _clipedImageWidth
    );
  }

  /**
   * Set cropped area in the thumbnail
   * @attr: _targetImageElement - affected thumbnail image
   * @attr: _cropArea - array of [cr_x1, cr_y1, cr_x2, cr_y2]
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
   * Set cropped area in the thumbnail
   * @attr: _targetImageElement - affected thumbnail image
   * @attr: _cropArea - array of [cr_x1, cr_y1, cr_x2, cr_y2]
   */
  this.cropedAreaSet = function (
    _targetImageElement,
    _clipedImageSrc,
    _targetImageOverheadStyle
  )
  {
    // this.cropedAreaRemove (_targetImageElement)

    // if (_cropAreaWidth==0 || _cropAreaHeight==0)
    //  return;

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
      // border: '1px solid red',
      overflow: 'hidden'
    }).appendTo(_toolSet);

    var _cropImg = jQuery('<IMG src="' + _clipedImageSrc + '" />').css({
      position: 'absolute',
      // maxWidth: 'auto'
    })
    _cropImg.appendTo(_cropAreaDiv);
  }

  /**
   * Remove cropped area from the thumbnail
   * @attr: _targetImageElement - affected thumbnail image
   */
  this.cropedAreaRemove = function () {
    // this.userImageThumb.element.prev('div.thumbCropedAreaToolSet').remove();
    this.templatePreview.element.prev('div.thumbCropedAreaToolSet').remove();
  }
}
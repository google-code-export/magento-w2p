/**
 * Class for storing any number of keys and values in specified html input element
 * @attr: _storageInputElement - html input element
 */
function metadataAccessor (_storageInputElement)
{
  /*
   * Constructor
   */
  this.metadataAccessor = function(_storageInputElement)
  {
    this._storageInputElement = _storageInputElement
  }
  this.metadataAccessor(_storageInputElement);

  /*
   * Restore metadata from the storage,
   * parse it into key-values pairs and set them as a properties of the object
   */
  this.restoreFromStorage = function()
  {
    var _metadata = top.userImageThumbSelected.data('metadata');
    var _key_val_pairs = (_metadata==null) ? [] : _metadata.split(';');
    // var _key_val_pairs = this._storageInputElement.value.split(';');
    for (var _i in _key_val_pairs) {
      [_key, _val] = _key_val_pairs[_i].split('=');
      this.setProperty(_key, _val);
    }
  }

  /*
   * Encode key-values pairs in string of special format,
   * ready for sending to serverside through GET request,
   * and store it
   */
  this.storeAll = function()
  {
    var _outArr = [];
    var _j = 0;
    for(var _i in this) if (typeof(this[_i])!='function' && _i != '_storageInputElement') {
      _outArr[_j++] = _i + '=' + this[_i];
    }
    var _metadata = _outArr.join(';');
    top.userImageThumbSelected.data('metadata', _metadata);
    if (jQuery('input[name=zetaprints-#' + top.image_imageName + ']:checked', top.document).val()==top.userImageThumbSelected.attr('id'))
      this._storageInputElement.value = _metadata;
  }

  /*
   * Clear all metadata
   */
  this.clearAll = function()
  {
    top.userImageThumbSelected.data('metadata', null);
    this._storageInputElement.value = '';
  }

  /*
   * Property setter
   */
  this.setProperty = function(_propertyName, _propertyValue)
  {
    this[_propertyName] = _propertyValue;
  }

  /*
   * Property getter
   */
  this.getProperty = function(_propertyName)
  {
    if(typeof(this[_propertyName])=='undefined')
      return null
    else
      return this[_propertyName];
  }
}
/**
 * Class for storing any number of keys and values in specified html input element
 *
 * @attr: _storageElement - element for storing metadata
 * @attr: _productFormInput - html input element
 */
function metadataAccessor (_storageElement, _productFormInput)
{
  /*
   * Constructor
   */
  this.metadataAccessor = function(_storageElement, _productFormInput)
  {
    this._storageElement = _storageElement;
    this._productFormInput = _productFormInput;
  }
  this.metadataAccessor(_storageElement, _productFormInput);

  /*
   * Restore metadata from the storage,
   * parse it into key-values pairs and set them as a properties of the object
   */
  this.restoreFromStorage = function()
  {
    var _metadata = this._storageElement.data('metadata');
    var _key_val_pairs = (_metadata==null) ? [] : _metadata.split(';');
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
    for(var _i in this) if (typeof(this[_i])!='function' && _i != '_storageElement' && _i != '_productFormInput') {
      _outArr[_j++] = _i + '=' + this[_i];
    }
    var _metadata = _outArr.join(';');
    this._storageElement.data('metadata', _metadata);

    // also place metadata in product form field for sending it on serverside
    // only if the image edited by user is checked by corresponding radio-button
    if (jQuery('input[type=radio]', this._storageElement.parents('td')).attr('checked') == true) {
      this._productFormInput.value = _metadata;
    }
  }

  /*
   * Clear all metadata
   */
  this.clearAll = function()
  {
    this._storageElement.data('metadata', null);
    if (jQuery('input[type=radio]', this._storageElement.parents('td')).attr('checked') == true) {
      this._productFormInput.value = '';
    }
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
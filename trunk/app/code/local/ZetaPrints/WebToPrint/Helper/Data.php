<?php
/**
 * OpenERP data helper
 */
class ZetaPrints_WebToPrint_Helper_Data extends Mage_Core_Helper_Abstract {

  public function _getUrl($route, $params = array()) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS) {
      $params['_secure'] = true;
      return parent::_getUrl($route, $params);
    }

    return parent::_getUrl($route, $params);
  }

  public function get_preview_url ($guid) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/preview/get',
                              array('guid' => $guid, '_secure' => true) );

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/preview/' . $guid;
  }

  public function get_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/thumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/thumb/' . $guid;
  }

  public function get_photo_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/photothumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/photothumbs/' . $guid;
  }

  public function get_image_editor_url ($guid) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/image/',
                              array('id' => $guid, 'iframe' => 1,
                                    '_secure' => true) );

    return parent::_getUrl('web-to-print/image/',
                            array('id' => $guid, 'iframe' => 1) );
  }

  public function create_url_for_product ($product, $query_params) {
    //Get model for URL
    $url_model = $product()->getUrlModel();

    $params = array();

    //Set parameter for Session ID in URL
    if (!Mage::app()->getUseSessionInUrl())
      $params['_nosid'] = true;

    //Add query parameters to URL
    $params['_query'] = $query_params;

    echo $url_model->getUrl($context->getProduct(), $params);
  }
}
